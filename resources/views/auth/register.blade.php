@extends('layouts.guest')

@section('content')
<!-- Interactive Education Background -->
<div class="fixed inset-0 z-0 overflow-hidden bg-[#0f172a]" x-data="particleSystem()">
    <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-indigo-600/30 blur-[120px] animate-pulse"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] rounded-full bg-blue-600/30 blur-[120px] animate-pulse" style="animation-delay: 2s;"></div>
    
    <template x-for="particle in particles" :key="particle.id">
        <div class="absolute text-white/10"
             :style="`left: ${particle.x}%; bottom: ${particle.y}%; width: ${particle.size}px; height: ${particle.size}px; transform: rotate(${particle.rotation}deg); transition: bottom 0.05s linear, transform 0.05s linear;`"
             x-html="icons[particle.iconIndex]">
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('particleSystem', () => ({
        particles: [],
        icons: [
            `<svg fill="currentColor" viewBox="0 0 24 24" class="w-full h-full"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V15H6.5A.5.5 0 0 0 6 15.5V19.5a.5.5 0 0 0 .5.5H20v-2H6.5a.5.5 0 0 0-.5.5v.5Z" /><path d="M6 3v11h14V3H6ZM4 3v12.5a2.5 2.5 0 0 0 .5 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Z" /></svg>`,
            `<svg fill="currentColor" viewBox="0 0 24 24" class="w-full h-full"><path d="M12 3L1 9L4 10.63V17C4 18.1 7.58 19 12 19C16.42 19 20 18.1 20 17V10.63L23 9L12 3ZM12 17C8.69 17 6 16.33 6 15.5V11.72L12 15L18 11.72V15.5C18 16.33 15.31 17 12 17ZM12 12.5L3.89 8L12 3.5L20.11 8L12 12.5ZM20 17V19C20 19 16.42 20 12 20C7.58 20 4 19 4 19V17H2V19.5C2 20.33 5.33 22 12 22C18.67 22 22 20.33 22 19.5V17H20Z" /></svg>`,
            `<svg fill="currentColor" viewBox="0 0 24 24" class="w-full h-full"><path d="M14.06 9.02L14.98 9.94L5.92 19H5V18.08L14.06 9.02ZM17.66 3C17.41 3 17.15 3.1 16.96 3.29L15.13 5.12L18.88 8.87L20.71 7.04C21.1 6.65 21.1 6.02 20.71 5.63L18.37 3.29C18.17 3.09 17.92 3 17.66 3ZM14.06 6.19L3 17.25V21H6.75L17.81 9.94L14.06 6.19Z" /></svg>`,
            `<svg fill="currentColor" viewBox="0 0 24 24" class="w-full h-full"><path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM11 19.93C7.06 19.43 4 16.05 4 12C4 11.68 4.03 11.36 4.08 11H7.53C7.72 13.9 8.65 16.59 10.08 18.91C10.37 19.26 10.68 19.6 11 19.93ZM10.5 10H4.35C4.7 7.23 6.48 4.9 8.94 3.73C8.42 5.56 8.08 7.63 8.08 10H10.5ZM13 19.93C13.32 19.6 13.63 19.26 13.92 18.91C15.35 16.59 16.28 13.9 16.47 11H13V19.93ZM13.5 10H15.92C15.92 7.63 15.58 5.56 15.06 3.73C17.52 4.9 19.3 7.23 19.65 10H13.5ZM19.92 11H16.47C16.28 13.9 15.35 16.59 13.92 18.91C13.63 19.26 13.32 19.6 13 19.93V19.93C16.94 19.43 20 16.05 20 12C20 11.68 19.97 11.36 19.92 11Z"/></svg>`
        ],
        init() {
            for(let i=0; i<15; i++) {
                this.particles.push(this.createParticle(Math.random() * 100));
            }
            setInterval(() => {
                this.particles.forEach(p => {
                    p.y += p.speed;
                    p.rotation += p.rotSpeed;
                    if(p.y > 110) {
                        Object.assign(p, this.createParticle(-10));
                    }
                });
            }, 50);
        },
        createParticle(startY) {
            return {
                id: Math.random().toString(36).substr(2, 9),
                x: Math.random() * 100,
                y: startY,
                size: Math.random() * 40 + 30, 
                speed: Math.random() * 0.2 + 0.05,
                rotation: Math.random() * 360,
                rotSpeed: (Math.random() - 0.5) * 1.5,
                iconIndex: Math.floor(Math.random() * this.icons.length)
            };
        }
    }));
});
</script>

<div class="relative z-10 w-full max-w-[400px]">
    <div class="bg-white rounded-lg shadow-2xl overflow-hidden border-t-[3px] border-indigo-600">
        
        <div class="px-6 py-5 text-center border-b border-slate-100">
            <a href="#" class="text-[28px] font-semibold text-slate-800 transition-colors">
                <b>Makassar</b><span class="font-light">Ujian</span>
            </a>
        </div>

        <div class="p-6">
            <p class="text-center text-slate-500 mb-5">Daftar keanggotaan baru</p>

            <form method="POST" action="{{ route('register') ?? '#' }}" x-data="{ loading: false }" @submit="loading = true">
                @csrf

                @if ($errors->any())
                    <div class="mb-4 rounded bg-red-50 p-3 text-sm text-red-600 border border-red-200">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                {{-- Banner info kode undangan --}}
                <div class="mb-4 rounded-lg bg-indigo-50 border border-indigo-200 p-3 flex gap-2 text-sm text-indigo-700">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/></svg>
                    <span>Pendaftaran memerlukan <strong>kode undangan</strong> dari admin sekolah Anda.</span>
                </div>

                <div class="space-y-4 mb-5">
                    <div class="relative flex items-center">
                        <div class="w-full relative">
                            <input id="registerFullName" name="name" type="text" required value="{{ old('name') }}" 
                                class="peer w-full rounded-l-md rounded-r-none border border-r-0 border-slate-300 px-3 pb-2 pt-6 text-sm text-slate-900 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 transition-colors placeholder-transparent"
                                placeholder="Nama Lengkap" />
                            <label for="registerFullName" class="absolute left-3 top-2 text-xs text-slate-500 transition-all peer-placeholder-shown:top-[14px] peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-indigo-600">Nama Lengkap</label>
                        </div>
                        <div class="flex items-center justify-center w-11 h-[50px] border border-l-0 border-slate-300 bg-slate-50 rounded-r-md">
                            <svg class="w-4 h-4 text-slate-500" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H3Zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                            </svg>
                        </div>
                    </div>

                    <div class="relative flex items-center">
                        <div class="w-full relative">
                            <input id="registerEmail" name="email" type="email" required value="{{ old('email') }}" 
                                class="peer w-full rounded-l-md rounded-r-none border border-r-0 border-slate-300 px-3 pb-2 pt-6 text-sm text-slate-900 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 transition-colors placeholder-transparent"
                                placeholder="Email" />
                            <label for="registerEmail" class="absolute left-3 top-2 text-xs text-slate-500 transition-all peer-placeholder-shown:top-[14px] peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-indigo-600">Email</label>
                        </div>
                        <div class="flex items-center justify-center w-11 h-[50px] border border-l-0 border-slate-300 bg-slate-50 rounded-r-md">
                            <svg class="w-4 h-4 text-slate-500" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757Zm3.436-.586L16 11.801V4.697l-5.803 3.546Z"/>
                            </svg>
                        </div>
                    </div>

                    <div class="relative" x-data="{ showPassword: false }">
                        <input id="registerPassword" name="password" :type="showPassword ? 'text' : 'password'" required
                            class="peer w-full rounded-md border border-slate-300 px-3 pr-10 pb-2 pt-6 text-sm text-slate-900 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 transition-colors placeholder-transparent"
                            placeholder="Kata Sandi" />
                        <label for="registerPassword" class="absolute left-3 top-2 text-xs text-slate-500 transition-all peer-placeholder-shown:top-[14px] peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-indigo-600">Kata Sandi</label>
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg x-show="!showPassword" class="w-5 h-5 text-slate-400 hover:text-slate-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg x-show="showPassword" style="display:none;" class="w-5 h-5 text-slate-400 hover:text-slate-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>

                    <div class="relative" x-data="{ showPassword: false }">
                        <input id="registerPasswordConfirmation" name="password_confirmation" :type="showPassword ? 'text' : 'password'" required
                            class="peer w-full rounded-md border border-slate-300 px-3 pr-10 pb-2 pt-6 text-sm text-slate-900 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 transition-colors placeholder-transparent"
                            placeholder="Konfirmasi Kata Sandi" />
                        <label for="registerPasswordConfirmation" class="absolute left-3 top-2 text-xs text-slate-500 transition-all peer-placeholder-shown:top-[14px] peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-indigo-600">Konfirmasi Kata Sandi</label>
                        <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg x-show="!showPassword" class="w-5 h-5 text-slate-400 hover:text-slate-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg x-show="showPassword" style="display:none;" class="w-5 h-5 text-slate-400 hover:text-slate-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    {{-- Kode Undangan --}}
                    <div>
                        <div class="relative flex items-center">
                            <div class="w-full relative">
                                <input id="inviteCode" name="invite_code" type="text"
                                    value="{{ old('invite_code', $inviteCode ?? '') }}"
                                    maxlength="8"
                                    class="peer w-full rounded-l-md rounded-r-none border border-r-0 {{ $errors->has('invite_code') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-slate-300 focus:border-indigo-600 focus:ring-indigo-600' }} px-3 pb-2 pt-6 text-sm text-slate-900 uppercase tracking-widest focus:outline-none focus:ring-1 transition-colors placeholder-transparent"
                                    placeholder="Kode Undangan" />
                                <label for="inviteCode" class="absolute left-3 top-2 text-xs text-slate-500 transition-all peer-placeholder-shown:top-[14px] peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs peer-focus:text-indigo-600">Kode Undangan</label>
                            </div>
                            <div class="flex items-center justify-center w-11 h-[50px] border border-l-0 {{ $errors->has('invite_code') ? 'border-red-400 bg-red-50' : 'border-slate-300 bg-slate-50' }} rounded-r-md">
                                <svg class="w-4 h-4 {{ $errors->has('invite_code') ? 'text-red-400' : 'text-slate-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
                            </div>
                        </div>
                        @error('invite_code')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center">
                        <input id="agreeTerms" name="terms" type="checkbox" required class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                        <label for="agreeTerms" class="ml-2 block text-[15px] text-slate-700 select-none cursor-pointer">
                            Saya setuju dengan <a href="#" class="text-indigo-600 hover:text-indigo-800 hover:underline">syarat dan ketentuan</a>
                        </label>
                    </div>
                    
                    <div class="w-[35%]">
                        <button type="submit" :disabled="loading" class="w-full rounded bg-indigo-600 px-4 py-2 text-[15px] font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 transition-colors flex justify-center items-center shadow-sm">
                            <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display:none;">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-show="!loading">Daftar</span>
                            <span x-show="loading" style="display:none;">...</span>
                        </button>
                    </div>
                </div>
            </form>

            <div class="mt-4 flex flex-col space-y-1 text-[15px] text-center">
                <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-800 transition-colors mt-2">Saya sudah memiliki keanggotaan</a>
            </div>
            
        </div>
    </div>
</div>
@endsection
