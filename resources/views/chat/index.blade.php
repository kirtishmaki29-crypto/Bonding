@extends('layouts.app', ['title' => 'Bonding · Conversations under the Stars'])

@section('content')
<div id="bonding-chat-app" 
     class="flex h-screen h-[100dvh] w-full flex-col overflow-hidden text-slate-100"
     data-current-user-id="{{ $currentUser->id }}"
     data-current-user-name="{{ $currentUser->name }}"
     data-current-user-avatar="{{ $currentUser->avatarUrl() }}"
     data-initial-conversation-id="{{ $initialConversationId ?? '' }}">

    <!-- Top Navigation Header -->
    <header class="relative z-20 flex h-16 w-full items-center justify-between border-b border-white/[0.08] bg-[#050b1e]/80 px-4 sm:px-6 backdrop-blur-xl shrink-0">
        <!-- Logo Branding -->
        <div class="flex items-center space-x-3">
            <a href="{{ route('chat.index') }}" class="flex items-center space-x-2.5 group">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-tr from-cyan-600 to-indigo-600 shadow-md shadow-cyan-500/20 ring-1 ring-white/20 transition-transform group-hover:scale-105">
                    <span class="text-lg">🌙</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-base font-bold tracking-tight bg-gradient-to-r from-sky-200 via-white to-indigo-200 bg-clip-text text-transparent">Bonding</span>
                    <span class="text-[10px] text-cyan-400/80 tracking-wide font-medium uppercase -mt-0.5">Under the Stars</span>
                </div>
            </a>
        </div>

        <!-- Current User Profile & Actions -->
        <div class="flex items-center space-x-3">
            <!-- User Status Card Trigger -->
            <button id="user-profile-btn" 
                    type="button"
                    class="flex items-center space-x-2.5 rounded-full p-1.5 pr-3 bg-white/[0.04] hover:bg-white/[0.08] border border-white/10 transition-all cursor-pointer">
                <div class="relative">
                    <img src="{{ $currentUser->avatarUrl() }}" alt="{{ $currentUser->name }}" class="h-8 w-8 rounded-full object-cover ring-1 ring-cyan-400/40">
                    <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-[#050b1e]"></span>
                </div>
                <div class="hidden sm:flex flex-col text-left">
                    <span class="text-xs font-medium text-slate-200 truncate max-w-[130px]">{{ $currentUser->name }}</span>
                    <span class="text-[10px] text-cyan-300/70 truncate max-w-[130px]">{{ $currentUser->headline ?? 'Explorer' }}</span>
                </div>
            </button>

            <!-- Logout Button -->
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" 
                        title="Sign out"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white/[0.04] hover:bg-rose-500/20 text-slate-400 hover:text-rose-300 border border-white/10 transition-all cursor-pointer">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </header>

    <!-- Main Workspace (Responsive Two-Column / Single-Column Mobile Toggle) -->
    <div class="relative flex flex-1 overflow-hidden">

        <!-- ====================================================================
             LEFT SIDEBAR: Conversations & Discover People
             ==================================================================== -->
        <aside id="chat-sidebar" 
               class="flex w-full md:w-80 lg:w-96 flex-col border-r border-white/[0.08] bg-[#040818]/70 backdrop-blur-xl shrink-0 transition-transform duration-300 z-10">

            <!-- Search Bar -->
            <div class="p-3.5 border-b border-white/[0.06]">
                <div class="relative flex items-center">
                    <svg class="absolute left-3.5 h-4 w-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input id="sidebar-search-input" 
                           type="text" 
                           placeholder="Search chats or find people..." 
                           class="w-full rounded-2xl glass-input py-2.5 pl-10 pr-4 text-xs placeholder-slate-400 border-white/[0.08] focus:border-cyan-400/50">
                    <button id="sidebar-search-clear" 
                            type="button" 
                            class="hidden absolute right-3 text-slate-400 hover:text-slate-200 text-xs">✕</button>
                </div>
            </div>

            <!-- Tab Switcher: Chats vs Discover -->
            <div class="flex items-center p-2 mx-3 my-2 rounded-2xl bg-white/[0.03] border border-white/[0.06]">
                <button id="tab-conversations-btn" 
                        type="button"
                        class="flex-1 py-1.5 px-3 rounded-xl text-xs font-medium text-white bg-cyan-600/30 border border-cyan-400/30 transition-all flex items-center justify-center space-x-1.5 shadow-sm">
                    <span>💬 Chats</span>
                    <span id="unread-total-badge" class="hidden px-1.5 py-0.2 rounded-full text-[10px] bg-cyan-400 text-slate-950 font-bold">0</span>
                </button>
                <button id="tab-discover-btn" 
                        type="button"
                        class="flex-1 py-1.5 px-3 rounded-xl text-xs font-medium text-slate-400 hover:text-slate-200 transition-all flex items-center justify-center space-x-1.5">
                    <span>✨ Discover People</span>
                </button>
            </div>

            <!-- Conversations List Container -->
            <div id="conversations-pane" class="flex-1 overflow-y-auto px-2 py-1 space-y-1">
                <!-- Loading State Skeleton -->
                <div id="conversations-loading" class="p-4 space-y-3">
                    <div class="flex items-center space-x-3 animate-pulse">
                        <div class="h-11 w-11 rounded-full bg-white/10"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 w-28 bg-white/10 rounded"></div>
                            <div class="h-2 w-44 bg-white/5 rounded"></div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 animate-pulse">
                        <div class="h-11 w-11 rounded-full bg-white/10"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 w-24 bg-white/10 rounded"></div>
                            <div class="h-2 w-36 bg-white/5 rounded"></div>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Conversation List Items populated by JS -->
                <div id="conversations-list" class="space-y-1"></div>

                <!-- Empty State -->
                <div id="conversations-empty" class="hidden p-8 text-center my-auto">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/5 mb-3 text-2xl">🌌</div>
                    <p class="text-sm font-medium text-slate-300">No conversations yet</p>
                    <p class="text-xs text-slate-400 mt-1">Switch to "Discover People" to connect with someone under the stars!</p>
                </div>
            </div>

            <!-- Discover People List Container (Tab 2) -->
            <div id="discover-pane" class="hidden flex-1 overflow-y-auto px-2 py-1 space-y-1">
                <!-- Discover People Items populated by JS -->
                <div id="discover-list" class="space-y-1.5"></div>

                <!-- Empty State for Search -->
                <div id="discover-empty" class="hidden p-8 text-center my-auto">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/5 mb-3 text-2xl">🔍</div>
                    <p class="text-sm font-medium text-slate-300">No people found</p>
                    <p class="text-xs text-slate-400 mt-1">Try another search term or explore all celestial wanderers.</p>
                </div>
            </div>

            <!-- Minimal Oceanic Sidebar Footer -->
            <div class="p-3 border-t border-white/[0.06] text-center text-[11px] text-slate-400 flex items-center justify-center space-x-2">
                <span>🌊 Ocean Depth</span>
                <span>·</span>
                <span>🌙 Soft Moonlight</span>
            </div>
        </aside>

        <!-- ====================================================================
             RIGHT MAIN AREA: Active Chat Room OR Welcoming Empty State
             ==================================================================== -->
        <main id="chat-main" class="flex-1 flex flex-col bg-[#020512]/60 backdrop-blur-md overflow-hidden relative">

            <!-- 1. EMPTY / WELCOME STATE (When no conversation is selected) -->
            <div id="chat-empty-state" class="flex flex-1 flex-col items-center justify-center p-6 text-center select-none">
                <div class="max-w-md mx-auto space-y-5">
                    <!-- Cosmic Moon Reflection Illustration -->
                    <div class="relative mx-auto w-28 h-28 flex items-center justify-center">
                        <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-cyan-500/20 to-indigo-500/20 blur-xl animate-pulse"></div>
                        <div class="relative w-24 h-24 rounded-full border border-cyan-400/30 bg-gradient-to-br from-indigo-950/80 via-slate-900/90 to-cyan-950/90 flex items-center justify-center shadow-2xl shadow-cyan-500/20">
                            <span class="text-5xl filter drop-shadow-[0_0_20px_rgba(56,189,248,0.6)]">🌙</span>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-xl sm:text-2xl font-semibold text-white tracking-tight">
                            The Night is Still and Welcoming
                        </h2>
                        <p class="text-sm text-slate-300/80 mt-2 leading-relaxed">
                            Select an existing conversation on the left, or open <strong class="text-cyan-300 font-medium">Discover People</strong> to connect and chat privately.
                        </p>
                    </div>

                    <div class="pt-2">
                        <button type="button" 
                                id="btn-discover-shortcut" 
                                class="inline-flex items-center space-x-2 py-2.5 px-5 rounded-2xl bg-cyan-600/30 hover:bg-cyan-600/50 border border-cyan-400/40 text-cyan-200 text-xs font-medium transition-all shadow-lg shadow-cyan-500/10 cursor-pointer">
                            <span>✨ Find Someone to Talk To</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. ACTIVE CONVERSATION CONTAINER (Hidden by default until conversation selected) -->
            <div id="chat-active-container" class="hidden flex-1 flex flex-col h-full overflow-hidden">

                <!-- Chat Room Top Header -->
                <header class="flex h-16 items-center justify-between border-b border-white/[0.08] bg-[#060c24]/90 px-4 sm:px-6 backdrop-blur-xl shrink-0 z-10">
                    <div class="flex items-center space-x-3 min-w-0">
                        <!-- Mobile Back Button -->
                        <button id="chat-back-btn" 
                                type="button" 
                                class="md:hidden flex h-9 w-9 items-center justify-center rounded-full bg-white/[0.05] hover:bg-white/[0.1] text-slate-300 border border-white/10 shrink-0 mr-1 cursor-pointer"
                                title="Back to conversations">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>

                        <!-- Recipient Avatar & Identity -->
                        <div class="relative shrink-0 cursor-pointer" id="active-recipient-avatar-btn">
                            <img id="active-user-avatar" src="" alt="User" class="h-10 w-10 rounded-full object-cover ring-2 ring-cyan-400/30">
                            <span id="active-user-status-dot" class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-[#060c24]"></span>
                        </div>

                        <div class="min-w-0 cursor-pointer" id="active-recipient-name-btn">
                            <h3 id="active-user-name" class="text-sm font-semibold text-white truncate"></h3>
                            <p id="active-user-headline" class="text-[11px] text-cyan-300/70 truncate"></p>
                        </div>
                    </div>

                    <!-- Header Options -->
                    <div class="flex items-center space-x-2 shrink-0">
                        <button id="active-profile-info-btn"
                                type="button" 
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-white/[0.04] hover:bg-white/[0.08] text-slate-300 border border-white/10 transition-all cursor-pointer"
                                title="View profile info">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                    </div>
                </header>

                <!-- Scrollable Messages Area -->
                <div id="messages-scroll-area" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
                    <!-- Date separator & messages inserted dynamically -->
                    <div id="messages-container" class="space-y-3.5"></div>
                </div>

                <!-- Bottom Message Input Bar (Sticky & Mobile Responsive) -->
                <footer class="border-t border-white/[0.08] bg-[#050a1e]/90 p-3 sm:p-4 backdrop-blur-xl shrink-0 z-10">
                    <form id="message-send-form" class="flex items-end space-x-2.5 max-w-4xl mx-auto" onsubmit="return false;">
                        <div class="relative flex-1">
                            <textarea id="message-input" 
                                      rows="1" 
                                      placeholder="Write a message under the moonlight... (Press Enter to send)" 
                                      maxlength="4000"
                                      class="w-full resize-none rounded-2xl glass-input py-3 pl-4 pr-12 text-sm leading-relaxed max-h-32 overflow-y-auto placeholder-slate-400 border-white/[0.1] focus:border-cyan-400/60"></textarea>

                            <!-- Quick Emoji Trigger -->
                            <button id="quick-emoji-btn" 
                                    type="button" 
                                    class="absolute right-3.5 bottom-2.5 text-slate-400 hover:text-cyan-300 text-base transition-colors cursor-pointer"
                                    title="Add emoji">
                                ✨
                            </button>
                        </div>

                        <!-- Send Button -->
                        <button id="message-send-btn" 
                                type="submit" 
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 text-white shadow-lg shadow-cyan-500/25 active:scale-95 transition-all shrink-0 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="h-5 w-5 transform translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </form>
                </footer>

            </div>
        </main>
    </div>

    <!-- ========================================================================
         MODAL 1: Current User Profile / Status Editor
         ======================================================================== -->
    <div id="user-profile-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
        <div class="glass-panel rounded-3xl p-6 w-full max-w-md border border-white/10 shadow-2xl relative">
            <button id="close-user-profile-modal" type="button" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">✕</button>
            <div class="flex items-center space-x-4 mb-5">
                <img src="{{ $currentUser->avatarUrl() }}" alt="{{ $currentUser->name }}" class="h-16 w-16 rounded-full object-cover ring-2 ring-cyan-400/50">
                <div>
                    <h3 class="text-base font-bold text-white">{{ $currentUser->name }}</h3>
                    <p class="text-xs text-slate-400">{{ $currentUser->email }}</p>
                    <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded-full text-[10px] font-medium bg-cyan-950/70 text-cyan-300 border border-cyan-500/30">
                        Authenticated User
                    </span>
                </div>
            </div>

            <form id="profile-headline-form" class="space-y-4">
                <div>
                    <label for="profile-headline-input" class="block text-xs font-medium text-slate-300 mb-1.5">
                        Your Personal Headline / Bio
                    </label>
                    <input id="profile-headline-input" 
                           type="text" 
                           value="{{ $currentUser->headline }}" 
                           placeholder="e.g. Stargazer · finding peace in ocean tides" 
                           maxlength="160"
                           class="w-full rounded-xl glass-input py-2.5 px-3.5 text-xs">
                </div>
                <div class="flex justify-end space-x-2 pt-2">
                    <button type="button" id="cancel-profile-btn" class="py-2 px-4 rounded-xl text-xs text-slate-300 hover:text-white bg-white/[0.05]">Close</button>
                    <button type="submit" id="save-profile-btn" class="py-2 px-4 rounded-xl text-xs font-medium text-slate-900 bg-cyan-400 hover:bg-cyan-300 transition-colors">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================
         MODAL 2: Recipient User Profile Card
         ======================================================================== -->
    <div id="recipient-profile-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
        <div class="glass-panel rounded-3xl p-6 w-full max-w-sm border border-white/10 shadow-2xl relative text-center">
            <button id="close-recipient-modal" type="button" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">✕</button>
            <div class="flex justify-center mb-4">
                <div class="relative">
                    <img id="modal-recipient-avatar" src="" alt="User" class="h-20 w-20 rounded-full object-cover ring-4 ring-cyan-400/30 shadow-xl">
                    <span id="modal-recipient-status-dot" class="absolute bottom-1 right-1 h-3.5 w-3.5 rounded-full bg-emerald-400 ring-2 ring-slate-900"></span>
                </div>
            </div>
            <h3 id="modal-recipient-name" class="text-lg font-bold text-white mb-1"></h3>
            <p id="modal-recipient-headline" class="text-xs text-slate-300 leading-relaxed px-4 mb-4"></p>
            <div class="p-3 rounded-2xl bg-white/[0.03] border border-white/[0.06] text-xs text-slate-400 flex items-center justify-center space-x-2 mb-4">
                <span id="modal-recipient-activity">Active under the quiet stars</span>
            </div>
            <div class="flex space-x-2">
                <button type="button" id="modal-start-chat-btn" class="flex-1 py-2.5 px-4 rounded-2xl bg-cyan-600/30 hover:bg-cyan-600/50 border border-cyan-400/40 text-cyan-200 text-xs font-medium transition-all">
                    Start Chat
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
