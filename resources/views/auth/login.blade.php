@extends('layouts.app', ['title' => 'Bonding · Welcome under the Moonlight'])

@section('content')
<main class="flex min-h-screen min-h-[100dvh] flex-col items-center justify-between p-4 sm:p-8">
    <!-- Top Branding Bar -->
    <header class="w-full max-w-5xl flex items-center justify-between py-4">
        <div class="flex items-center space-x-3 group">
            <div class="relative flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-tr from-cyan-600 to-indigo-600 shadow-lg shadow-cyan-500/20 ring-1 ring-white/20 transition-transform group-hover:scale-105">
                <span class="text-xl">🌙</span>
            </div>
            <div>
                <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-sky-200 via-white to-indigo-200 bg-clip-text text-transparent">Bonding</span>
                <span class="hidden sm:inline-block ml-2 text-xs font-medium tracking-wide text-cyan-400/80 uppercase">People Connection</span>
            </div>
        </div>

        <div class="flex items-center space-x-2 text-xs text-slate-400">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Private & Secure</span>
        </div>
    </header>

    <!-- Central Welcome & Google Sign-In Card -->
    <div class="w-full max-w-md my-auto py-8">
        <div class="glass-panel rounded-3xl p-6 sm:p-10 border border-white/10 shadow-2xl shadow-black/60 relative overflow-hidden backdrop-blur-2xl">
            <!-- Subtle cosmic top glow line -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-cyan-400/60 to-transparent"></div>

            <!-- Atmosphere Icon Badge -->
            <div class="flex justify-center mb-6">
                <div class="relative">
                    <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-indigo-900/60 via-slate-900/80 to-cyan-950/70 border border-cyan-500/30 flex items-center justify-center shadow-inner shadow-cyan-400/10">
                        <span class="text-4xl filter drop-shadow-[0_0_15px_rgba(56,189,248,0.5)]">🌊</span>
                    </div>
                    <div class="absolute -top-1 -right-1 w-7 h-7 rounded-full bg-slate-900 border border-cyan-400/40 flex items-center justify-center shadow-md">
                        <span class="text-xs">✨</span>
                    </div>
                </div>
            </div>

            <!-- Header Titles -->
            <div class="text-center mb-8">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-white mb-2">
                    Connect under the Stars
                </h1>
                <p class="text-sm text-slate-300/90 leading-relaxed">
                    A calm, private space to meet authentic people and chat one-to-one beneath the moonlight.
                </p>
            </div>

            <!-- Flash Error Message -->
            @if(session('error'))
                <div class="mb-6 p-3.5 rounded-2xl bg-rose-950/40 border border-rose-500/30 text-rose-200 text-xs leading-relaxed flex items-start space-x-2.5">
                    <span class="text-rose-400 text-sm mt-0.5">⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Primary Google Login Action -->
            <div class="space-y-4">
                <a href="{{ route('auth.google') }}"
                   class="group relative flex items-center justify-center w-full py-3.5 px-5 rounded-2xl bg-white text-slate-900 font-medium text-sm transition-all duration-300 hover:bg-slate-100 hover:shadow-lg hover:shadow-cyan-500/25 active:scale-[0.98]">
                    <!-- Google SVG Icon -->
                    <svg class="w-5 h-5 mr-3 transition-transform group-hover:scale-110" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>Continue with Google</span>
                </a>

                @if(!$googleConfigured)
                    <div class="p-3 rounded-xl bg-cyan-950/30 border border-cyan-500/20 text-center">
                        <p class="text-xs text-cyan-300/80">
                            💡 Testing environment ready. Select a persona below to explore immediately without API keys!
                        </p>
                    </div>
                @endif
            </div>

            <!-- Instant Demo Personas for Quick Evaluation -->
            @if(isset($demoUsers) && $demoUsers->isNotEmpty())
                <div class="mt-8 pt-6 border-t border-white/10">
                    <p class="text-xs font-medium text-slate-400 text-center uppercase tracking-wider mb-4">
                        Quick Demo Exploration
                    </p>
                    <div class="grid grid-cols-2 gap-2.5">
                        @foreach($demoUsers as $user)
                            <a href="{{ route('auth.demo', ['email' => $user->email]) }}"
                               class="flex items-center space-x-2.5 p-2 rounded-xl bg-white/[0.04] hover:bg-white/[0.09] border border-white/5 hover:border-cyan-500/30 transition-all text-left group">
                                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover ring-1 ring-cyan-400/30">
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-medium text-slate-200 truncate group-hover:text-cyan-300">
                                        {{ $user->name }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 truncate">
                                        {{ Str::before($user->email, '@') }}
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Security Assurance Note -->
            <div class="mt-8 flex items-center justify-center space-x-2 text-[11px] text-slate-400">
                <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>End-to-end authenticated private conversations</span>
            </div>
        </div>
    </div>

    <!-- Minimal Footer -->
    <footer class="w-full max-w-5xl py-4 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-2">
        <p>Bonding &copy; {{ date('Y') }} · Where deep ocean meets the night sky.</p>
        <div class="flex items-center space-x-4">
            <span class="hover:text-slate-300">Simple</span>
            <span>·</span>
            <span class="hover:text-slate-300">Friendly</span>
            <span>·</span>
            <span class="hover:text-slate-300">Calm</span>
        </div>
    </footer>
</main>
@endsection
