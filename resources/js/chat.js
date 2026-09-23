/**
 * Bonding - Chat Application Client Manager
 * Modern, responsive, friendly one-to-one chat under the stars.
 */

document.addEventListener('DOMContentLoaded', () => {
    const appEl = document.getElementById('bonding-chat-app');
    if (!appEl) return;

    const currentUserId = parseInt(appEl.dataset.currentUserId, 10);
    const initialConversationId = appEl.dataset.initialConversationId;

    // DOM Elements
    const sidebar = document.getElementById('chat-sidebar');
    const chatMain = document.getElementById('chat-main');
    const emptyState = document.getElementById('chat-empty-state');
    const activeContainer = document.getElementById('chat-active-container');

    const searchInput = document.getElementById('sidebar-search-input');
    const searchClear = document.getElementById('sidebar-search-clear');
    const tabConvsBtn = document.getElementById('tab-conversations-btn');
    const tabDiscoverBtn = document.getElementById('tab-discover-btn');
    const convsPane = document.getElementById('conversations-pane');
    const discoverPane = document.getElementById('discover-pane');
    const convsLoading = document.getElementById('conversations-loading');
    const convsList = document.getElementById('conversations-list');
    const convsEmpty = document.getElementById('conversations-empty');
    const discoverList = document.getElementById('discover-list');
    const discoverEmpty = document.getElementById('discover-empty');
    const unreadTotalBadge = document.getElementById('unread-total-badge');

    // Active Chat DOM Elements
    const backBtn = document.getElementById('chat-back-btn');
    const activeAvatar = document.getElementById('active-user-avatar');
    const activeStatusDot = document.getElementById('active-user-status-dot');
    const activeName = document.getElementById('active-user-name');
    const activeHeadline = document.getElementById('active-user-headline');
    const messagesScroll = document.getElementById('messages-scroll-area');
    const messagesContainer = document.getElementById('messages-container');
    const messageInput = document.getElementById('message-input');
    const messageSendBtn = document.getElementById('message-send-btn');
    const quickEmojiBtn = document.getElementById('quick-emoji-btn');
    const btnDiscoverShortcut = document.getElementById('btn-discover-shortcut');

    // Modals
    const userProfileBtn = document.getElementById('user-profile-btn');
    const userProfileModal = document.getElementById('user-profile-modal');
    const closeUserProfileModal = document.getElementById('close-user-profile-modal');
    const cancelProfileBtn = document.getElementById('cancel-profile-btn');
    const profileHeadlineForm = document.getElementById('profile-headline-form');
    const profileHeadlineInput = document.getElementById('profile-headline-input');

    const activeProfileInfoBtn = document.getElementById('active-profile-info-btn');
    const activeRecipientAvatarBtn = document.getElementById('active-recipient-avatar-btn');
    const activeRecipientNameBtn = document.getElementById('active-recipient-name-btn');
    const recipientModal = document.getElementById('recipient-profile-modal');
    const closeRecipientModal = document.getElementById('close-recipient-modal');
    const modalRecipientAvatar = document.getElementById('modal-recipient-avatar');
    const modalRecipientStatusDot = document.getElementById('modal-recipient-status-dot');
    const modalRecipientName = document.getElementById('modal-recipient-name');
    const modalRecipientHeadline = document.getElementById('modal-recipient-headline');
    const modalRecipientActivity = document.getElementById('modal-recipient-activity');
    const modalStartChatBtn = document.getElementById('modal-start-chat-btn');

    // State
    let activeConversationId = null;
    let activeOtherUser = null;
    let conversations = [];
    let users = [];
    let activeTab = 'conversations'; // 'conversations' | 'discover'
    let pollTimer = null;
    let knownMessageIds = new Set();

    // Helper: CSRF Token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
    };

    // Helper: Escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Tab Navigation
    function switchTab(tab) {
        activeTab = tab;
        if (tab === 'conversations') {
            tabConvsBtn.className = 'flex-1 py-1.5 px-3 rounded-xl text-xs font-medium text-white bg-cyan-600/30 border border-cyan-400/30 transition-all flex items-center justify-center space-x-1.5 shadow-sm';
            tabDiscoverBtn.className = 'flex-1 py-1.5 px-3 rounded-xl text-xs font-medium text-slate-400 hover:text-slate-200 transition-all flex items-center justify-center space-x-1.5';
            convsPane.classList.remove('hidden');
            discoverPane.classList.add('hidden');
            renderConversations();
        } else {
            tabDiscoverBtn.className = 'flex-1 py-1.5 px-3 rounded-xl text-xs font-medium text-white bg-cyan-600/30 border border-cyan-400/30 transition-all flex items-center justify-center space-x-1.5 shadow-sm';
            tabConvsBtn.className = 'flex-1 py-1.5 px-3 rounded-xl text-xs font-medium text-slate-400 hover:text-slate-200 transition-all flex items-center justify-center space-x-1.5';
            discoverPane.classList.remove('hidden');
            convsPane.classList.add('hidden');
            loadUsers(searchInput.value.trim());
        }
    }

    tabConvsBtn.addEventListener('click', () => switchTab('conversations'));
    tabDiscoverBtn.addEventListener('click', () => switchTab('discover'));
    if (btnDiscoverShortcut) {
        btnDiscoverShortcut.addEventListener('click', () => switchTab('discover'));
    }

    // Search Input
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();
        searchClear.classList.toggle('hidden', query.length === 0);

        if (activeTab === 'conversations') {
            renderConversations(query);
        } else {
            loadUsers(query);
        }
    });

    searchClear.addEventListener('click', () => {
        searchInput.value = '';
        searchClear.classList.add('hidden');
        if (activeTab === 'conversations') {
            renderConversations();
        } else {
            loadUsers();
        }
    });

    // Mobile View Toggle
    function showMobileChat() {
        if (window.innerWidth < 768) {
            sidebar.classList.add('hidden');
            chatMain.classList.remove('hidden');
        }
    }

    function showMobileSidebar() {
        if (window.innerWidth < 768) {
            sidebar.classList.remove('hidden');
            chatMain.classList.add('hidden');
        }
    }

    backBtn.addEventListener('click', () => {
        showMobileSidebar();
    });

    // Window Resize Handling
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('hidden');
            chatMain.classList.remove('hidden');
        } else {
            if (activeConversationId) {
                showMobileChat();
            } else {
                showMobileSidebar();
            }
        }
    });

    // Fetch Conversations
    async function loadConversations(isPolling = false) {
        try {
            const res = await fetch('/api/conversations', { headers });
            if (!res.ok) return;
            const data = await res.json();
            conversations = data.conversations || [];

            // Update unread total
            const totalUnread = conversations.reduce((acc, c) => acc + (c.unread_count || 0), 0);
            if (totalUnread > 0) {
                unreadTotalBadge.textContent = totalUnread > 99 ? '99+' : totalUnread;
                unreadTotalBadge.classList.remove('hidden');
            } else {
                unreadTotalBadge.classList.add('hidden');
            }

            if (!isPolling || activeTab === 'conversations') {
                renderConversations(searchInput.value.trim());
            }

            if (convsLoading) convsLoading.classList.add('hidden');

            // Handle initial conversation parameter if present
            if (!isPolling && initialConversationId && !activeConversationId) {
                const target = conversations.find(c => c.id == initialConversationId);
                if (target) {
                    selectConversation(target.id);
                }
            }
        } catch (e) {
            console.error('Failed to load conversations:', e);
            if (convsLoading) convsLoading.classList.add('hidden');
        }
    }

    // Render Conversations
    function renderConversations(filter = '') {
        const query = filter.toLowerCase();
        const filtered = conversations.filter(c => {
            if (!query) return true;
            const nameMatch = c.other_user?.name?.toLowerCase().includes(query);
            const msgMatch = c.latest_message?.body?.toLowerCase().includes(query);
            return nameMatch || msgMatch;
        });

        convsList.innerHTML = '';

        if (filtered.length === 0) {
            convsEmpty.classList.remove('hidden');
            return;
        }

        convsEmpty.classList.add('hidden');

        filtered.forEach(c => {
            const isActive = c.id === activeConversationId;
            const other = c.other_user;
            const latest = c.latest_message;
            const hasUnread = (c.unread_count || 0) > 0;

            const item = document.createElement('div');
            item.className = `group flex items-center space-x-3 p-2.5 rounded-2xl cursor-pointer transition-all duration-200 border ${
                isActive 
                    ? 'bg-cyan-950/40 border-cyan-500/40 shadow-md shadow-cyan-900/20' 
                    : 'bg-white/[0.02] hover:bg-white/[0.06] border-white/[0.04] hover:border-white/[0.1]'
            }`;

            let msgSnippet = 'No messages yet';
            if (latest) {
                const prefix = latest.is_sender ? 'You: ' : '';
                msgSnippet = prefix + latest.body;
            }

            item.innerHTML = `
                <div class="relative shrink-0">
                    <img src="${escapeHtml(other.avatar)}" alt="${escapeHtml(other.name)}" class="h-11 w-11 rounded-full object-cover ring-1 ${other.is_online ? 'ring-emerald-400/50' : 'ring-white/10'}">
                    ${other.is_online ? '<span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-[#040818]"></span>' : ''}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between mb-0.5">
                        <span class="text-xs font-semibold ${isActive ? 'text-cyan-200' : 'text-slate-200'} truncate group-hover:text-cyan-300">
                            ${escapeHtml(other.name)}
                        </span>
                        ${latest ? `<span class="text-[10px] text-slate-400 shrink-0 ml-1">${escapeHtml(latest.time)}</span>` : ''}
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] truncate max-w-[170px] ${hasUnread ? 'text-cyan-200 font-semibold' : 'text-slate-400'}">
                            ${escapeHtml(msgSnippet)}
                        </p>
                        ${hasUnread ? `<span class="px-1.5 py-0.2 rounded-full text-[10px] bg-cyan-400 text-slate-950 font-bold shrink-0 ml-1.5">${c.unread_count}</span>` : ''}
                    </div>
                </div>
            `;

            item.addEventListener('click', () => {
                selectConversation(c.id);
            });

            convsList.appendChild(item);
        });
    }

    // Fetch Discover Users
    async function loadUsers(query = '') {
        try {
            const url = query ? `/api/users?q=${encodeURIComponent(query)}` : '/api/users';
            const res = await fetch(url, { headers });
            if (!res.ok) return;
            const data = await res.json();
            users = data.users || [];
            renderUsers();
        } catch (e) {
            console.error('Failed to load users:', e);
        }
    }

    // Render Discover Users
    function renderUsers() {
        discoverList.innerHTML = '';

        if (users.length === 0) {
            discoverEmpty.classList.remove('hidden');
            return;
        }

        discoverEmpty.classList.add('hidden');

        users.forEach(u => {
            const card = document.createElement('div');
            card.className = 'flex items-center space-x-3 p-3 rounded-2xl bg-white/[0.02] hover:bg-white/[0.06] border border-white/[0.05] hover:border-cyan-500/30 transition-all';

            card.innerHTML = `
                <div class="relative shrink-0">
                    <img src="${escapeHtml(u.avatar)}" alt="${escapeHtml(u.name)}" class="h-11 w-11 rounded-full object-cover ring-1 ring-cyan-400/30">
                    ${u.is_online ? '<span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-[#040818]"></span>' : ''}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-200 truncate">${escapeHtml(u.name)}</span>
                        ${u.is_online ? '<span class="text-[10px] text-emerald-400/90 font-medium">Online</span>' : '<span class="text-[10px] text-slate-400">' + escapeHtml(u.last_active) + '</span>'}
                    </div>
                    <p class="text-[11px] text-cyan-300/70 truncate mt-0.5">${escapeHtml(u.headline)}</p>
                </div>
                <button type="button" class="btn-connect-user shrink-0 py-1.5 px-3 rounded-xl bg-cyan-600/30 hover:bg-cyan-500/50 border border-cyan-400/40 text-cyan-200 text-xs font-medium transition-all shadow-sm">
                    Chat
                </button>
            `;

            // Start chat action
            card.querySelector('.btn-connect-user').addEventListener('click', (e) => {
                e.stopPropagation();
                startChatWithUser(u.id);
            });

            // Card click also shows recipient modal
            card.addEventListener('click', () => {
                openRecipientModal(u);
            });

            discoverList.appendChild(card);
        });
    }

    // Start Chat With User
    async function startChatWithUser(targetUserId) {
        try {
            const res = await fetch(`/api/users/${targetUserId}/start`, {
                method: 'POST',
                headers,
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.success && data.conversation_id) {
                switchTab('conversations');
                await loadConversations();
                selectConversation(data.conversation_id);
            }
        } catch (e) {
            console.error('Error starting conversation:', e);
        }
    }

    // Select Active Conversation
    async function selectConversation(convId) {
        activeConversationId = convId;
        emptyState.classList.add('hidden');
        activeContainer.classList.remove('hidden');

        showMobileChat();
        renderConversations(searchInput.value.trim());

        // Clear messages while loading
        messagesContainer.innerHTML = `
            <div class="flex justify-center p-8">
                <div class="inline-flex items-center space-x-2 text-xs text-cyan-300/70 animate-pulse">
                    <span>✨</span>
                    <span>Reaching across the starlight...</span>
                </div>
            </div>
        `;

        await fetchMessages(convId);
        messageInput.focus();
    }

    // Fetch Messages for Conversation
    async function fetchMessages(convId, isPolling = false) {
        if (activeConversationId !== convId) return;

        try {
            const res = await fetch(`/api/conversations/${convId}/messages`, { headers });
            if (!res.ok) {
                if (res.status === 403) {
                    alert('You are not authorized to view this private conversation.');
                    activeConversationId = null;
                    activeContainer.classList.add('hidden');
                    emptyState.classList.remove('hidden');
                }
                return;
            }

            const data = await res.json();
            if (activeConversationId !== convId) return;

            activeOtherUser = data.other_user;

            // Update Header
            activeAvatar.src = activeOtherUser.avatar;
            activeName.textContent = activeOtherUser.name;
            activeHeadline.textContent = activeOtherUser.is_online 
                ? 'Active under the stars · Online' 
                : (activeOtherUser.headline || 'Seen ' + activeOtherUser.last_active);
            activeStatusDot.className = `absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full ring-2 ring-[#060c24] ${
                activeOtherUser.is_online ? 'bg-emerald-400' : 'bg-slate-500'
            }`;

            // Render Messages
            const messages = data.messages || [];
            let newMessagesAdded = false;

            if (!isPolling) {
                messagesContainer.innerHTML = '';
                knownMessageIds.clear();
            }

            let lastDate = '';

            messages.forEach(msg => {
                if (!knownMessageIds.has(msg.id)) {
                    knownMessageIds.add(msg.id);
                    newMessagesAdded = true;

                    // Date divider if date changed
                    if (msg.full_date !== lastDate) {
                        lastDate = msg.full_date;
                        const divider = document.createElement('div');
                        divider.className = 'flex items-center justify-center my-3 select-none';
                        divider.innerHTML = `
                            <span class="px-3 py-1 rounded-full text-[10px] font-medium text-cyan-300/80 bg-white/[0.04] border border-white/[0.06] backdrop-blur-sm shadow-sm">
                                ${escapeHtml(msg.full_date)}
                            </span>
                        `;
                        messagesContainer.appendChild(divider);
                    }

                    const bubble = createMessageBubble(msg);
                    messagesContainer.appendChild(bubble);
                }
            });

            if (newMessagesAdded || !isPolling) {
                scrollToBottom();
            }
        } catch (e) {
            console.error('Error fetching messages:', e);
        }
    }

    // Create Message Bubble Element
    function createMessageBubble(msg) {
        const row = document.createElement('div');
        row.className = `flex w-full ${msg.is_sender ? 'justify-end' : 'justify-start'} group`;
        row.id = `msg-${msg.id}`;

        const isMe = msg.is_sender;

        if (isMe) {
            // Current User's Message: Glowing Cyan-to-Indigo Bubble (Right)
            row.innerHTML = `
                <div class="max-w-[85%] sm:max-w-[70%] flex flex-col items-end">
                    <div class="relative px-4 py-2.5 rounded-2xl rounded-tr-sm bg-gradient-to-r from-cyan-600 via-sky-600 to-indigo-600 text-white shadow-lg shadow-cyan-900/30 border border-cyan-400/30 break-words text-xs sm:text-sm leading-relaxed">
                        ${escapeHtml(msg.body)}
                    </div>
                    <div class="flex items-center space-x-1.5 mt-1 mr-1 text-[10px] text-slate-400">
                        <span>${escapeHtml(msg.created_at)}</span>
                        <span class="text-cyan-400/90 text-xs">✓</span>
                    </div>
                </div>
            `;
        } else {
            // Recipient's Message: Translucent Midnight Glass Bubble (Left)
            row.innerHTML = `
                <div class="max-w-[85%] sm:max-w-[70%] flex items-start space-x-2.5">
                    <img src="${escapeHtml(activeOtherUser?.avatar || '')}" alt="" class="h-7 w-7 rounded-full object-cover ring-1 ring-white/10 shrink-0 mt-0.5">
                    <div class="flex flex-col items-start">
                        <span class="text-[10px] font-medium text-slate-400 ml-1 mb-0.5">${escapeHtml(activeOtherUser?.name || '')}</span>
                        <div class="relative px-4 py-2.5 rounded-2xl rounded-tl-sm bg-[#0c1635]/85 text-slate-100 shadow-md border border-white/[0.08] break-words text-xs sm:text-sm leading-relaxed backdrop-blur-md">
                            ${escapeHtml(msg.body)}
                        </div>
                        <span class="text-[10px] text-slate-400 mt-1 ml-1">${escapeHtml(msg.created_at)}</span>
                    </div>
                </div>
            `;
        }

        return row;
    }

    // Scroll to Bottom
    function scrollToBottom() {
        messagesScroll.scrollTop = messagesScroll.scrollHeight;
    }

    // Auto-expand Textarea & Send Handling
    messageInput.addEventListener('input', () => {
        messageInput.style.height = 'auto';
        messageInput.style.height = Math.min(messageInput.scrollHeight, 128) + 'px';
    });

    messageInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    messageSendBtn.addEventListener('click', (e) => {
        e.preventDefault();
        sendMessage();
    });

    // Send Message
    async function sendMessage() {
        if (!activeConversationId) return;

        const body = messageInput.value.trim();
        if (!body) return;

        // Reset Input Immediately
        messageInput.value = '';
        messageInput.style.height = 'auto';
        messageInput.focus();

        // Optimistic Bubble
        const tempId = 'temp-' + Date.now();
        const optimisticMsg = {
            id: tempId,
            body: body,
            is_sender: true,
            created_at: 'Just now',
            full_date: 'Today',
            is_read: false,
        };

        const bubble = createMessageBubble(optimisticMsg);
        messagesContainer.appendChild(bubble);
        scrollToBottom();

        try {
            messageSendBtn.disabled = true;

            const res = await fetch(`/api/conversations/${activeConversationId}/messages`, {
                method: 'POST',
                headers,
                body: JSON.stringify({ body }),
            });

            messageSendBtn.disabled = false;

            if (res.ok) {
                const data = await res.json();
                if (data.message) {
                    knownMessageIds.add(data.message.id);
                    bubble.id = `msg-${data.message.id}`;
                    bubble.querySelector('.text-\\[10px\\] span').textContent = data.message.created_at;
                }
                // Refresh conversations list to update latest snippet
                loadConversations(true);
            } else {
                bubble.classList.add('opacity-50');
                const errSpan = document.createElement('span');
                errSpan.className = 'text-[10px] text-rose-400 mt-1';
                errSpan.textContent = 'Could not deliver. Tap to retry.';
                bubble.appendChild(errSpan);
            }
        } catch (e) {
            messageSendBtn.disabled = false;
            console.error('Error sending message:', e);
        }
    }

    // Quick Emoji Insert
    const celestialEmojis = ['✨', '🌙', '🌊', '🪐', '🌌', '💫', '💙', '⭐'];
    let emojiIdx = 0;
    quickEmojiBtn.addEventListener('click', () => {
        const emoji = celestialEmojis[emojiIdx % celestialEmojis.length];
        emojiIdx++;
        messageInput.value += emoji + ' ';
        messageInput.focus();
    });

    // Profile Modals
    userProfileBtn.addEventListener('click', () => {
        userProfileModal.classList.remove('hidden');
    });

    closeUserProfileModal.addEventListener('click', () => {
        userProfileModal.classList.add('hidden');
    });

    cancelProfileBtn.addEventListener('click', () => {
        userProfileModal.classList.add('hidden');
    });

    profileHeadlineForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const headline = profileHeadlineInput.value.trim();
        try {
            const res = await fetch('/api/profile', {
                method: 'POST',
                headers,
                body: JSON.stringify({ headline }),
            });
            if (res.ok) {
                const data = await res.json();
                const userHeadlineEl = userProfileBtn.querySelector('.text-\\[10px\\]');
                if (userHeadlineEl) userHeadlineEl.textContent = data.headline || 'Explorer';
                userProfileModal.classList.add('hidden');
            }
        } catch (e) {
            console.error('Failed to update profile:', e);
        }
    });

    // Recipient Profile Modal
    function openRecipientModal(user) {
        if (!user) return;
        modalRecipientAvatar.src = user.avatar;
        modalRecipientName.textContent = user.name;
        modalRecipientHeadline.textContent = user.headline || 'Quiet wanderer under the night sky';
        modalRecipientActivity.textContent = user.is_online ? 'Active now' : 'Seen ' + user.last_active;
        modalRecipientStatusDot.className = `absolute bottom-1 right-1 h-3.5 w-3.5 rounded-full ring-2 ring-slate-900 ${
            user.is_online ? 'bg-emerald-400' : 'bg-slate-500'
        }`;

        modalStartChatBtn.onclick = () => {
            recipientModal.classList.add('hidden');
            startChatWithUser(user.id);
        };

        recipientModal.classList.remove('hidden');
    }

    if (activeProfileInfoBtn) {
        activeProfileInfoBtn.addEventListener('click', () => openRecipientModal(activeOtherUser));
    }
    if (activeRecipientAvatarBtn) {
        activeRecipientAvatarBtn.addEventListener('click', () => openRecipientModal(activeOtherUser));
    }
    if (activeRecipientNameBtn) {
        activeRecipientNameBtn.addEventListener('click', () => openRecipientModal(activeOtherUser));
    }
    closeRecipientModal.addEventListener('click', () => {
        recipientModal.classList.add('hidden');
    });

    // Background Polling Loop for Real-Time Feel
    function startPolling() {
        pollTimer = setInterval(() => {
            if (activeConversationId) {
                fetchMessages(activeConversationId, true);
            }
            loadConversations(true);
        }, 3500);
    }

    // Initial Bootstrap
    loadConversations();
    startPolling();
});
