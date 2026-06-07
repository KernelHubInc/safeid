<header 
    x-data="{ 
        mobileMenuOpen: false, 
        scrolled: false, 
        showOverlay: false, 
        topOffset: '5',
        evaluateScrollPosition(){
            if(window.pageYOffset > this.topOffset){
                this.scrolled = true;
            } else {
                this.scrolled = false;
            }
        } 
    }"
    x-init="
        window.addEventListener('resize', function() {
            if(window.innerWidth > 768) {
                mobileMenuOpen = false;
            }
        });
        $watch('mobileMenuOpen', function(value){
            if(value){ document.body.classList.add('overflow-hidden'); } else { document.body.classList.remove('overflow-hidden'); }
        });
        evaluateScrollPosition();
        window.addEventListener('scroll', function() {
            evaluateScrollPosition(); 
        })
    " 
    :class="{ 'border-gray-200/60 bg-white/90 border-b backdrop-blur-lg' : scrolled, 'border-transparent border-b bg-white translate-y-0' : !scrolled }" 
    class="box-content sticky top-0 z-50 w-full h-24" 
>
    <div 
        x-show="showOverlay"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="absolute inset-0 w-full h-screen pt-24" x-cloak>
        <div class="w-screen h-full bg-black/50"></div>
    </div>
    <x-container class="container">
        <div class="z-30 flex items-center justify-between h-24 md:space-x-8">
            <div class="z-20 flex items-center justify-between w-full md:w-auto">
                <div class="relative z-20 inline-flex">
                    <a href="{{ route('home') }}" wire:navigate class="flex items-center justify-center space-x-3 font-bold text-zinc-900">
                    <x-logo class="w-auto h-8 md:h-9"></x-logo>
                    </a>
                </div>
                <div class="flex justify-end flex-grow md:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 transition duration-150 ease-in-out rounded-full text-zinc-400 hover:text-zinc-500 hover:bg-zinc-100">
                        <svg x-show="!mobileMenuOpen" class="w-6 h-6" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                        <svg x-show="mobileMenuOpen" class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <nav :class="{ 'hidden' : !mobileMenuOpen, 'block md:relative absolute top-0 left-0 md:w-auto w-screen md:h-auto h-screen pointer-events-none md:z-10 z-10' : mobileMenuOpen }" class="h-full md:flex">
                <ul :class="{ 'hidden md:flex' : !mobileMenuOpen, 'flex flex-col absolute md:relative md:w-auto w-screen h-full md:h-full md:overflow-auto overflow-scroll md:pt-0 mt-24 md:pb-0 pb-48 bg-white md:bg-transparent' : mobileMenuOpen }" id="menu" class="flex items-stretch justify-start flex-1 w-full h-full ml-0 border-t border-gray-100 pointer-events-auto md:items-center md:justify-center gap-x-8 md:w-auto md:border-t-0 md:flex-row">
                    <!-- <li x-data="{ open: false }" @mouseenter="showOverlay=true" @mouseleave="showOverlay=false" class="z-30 flex flex-col items-start h-auto border-b border-gray-100 md:h-full md:border-b-0 group md:flex-row md:items-center">
                        <a href="#_" x-on:click="open=!open" class="flex items-center w-full h-16 gap-1 text-sm font-semibold text-gray-700 transition duration-300 hover:bg-gray-100 md:hover:bg-transparent px-7 md:h-full md:px-0 md:w-auto hover:text-gray-900">
                            <span class="">Partners</span>
                            <svg :class="{ 'group-hover:-rotate-180' : !mobileMenuOpen, '-rotate-180' : mobileMenuOpen && open }" class="w-5 h-5 transition-all duration-300 ease-out" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" class=""></path></svg>
                        </a>
                        <div 
                            :class="{ 'hidden md:block opacity-0 invisible md:absolute' : !open, 'md:invisible md:opacity-0 md:hidden md:absolute' : open }"
                            class="top-0 left-0 w-screen space-y-3 transition-transform duration-300 ease-out bg-white border-t border-b border-gray-100 md:shadow-md md:-translate-y-2 md:mt-24 md:block md:group-hover:block md:group-hover:visible md:group-hover:opacity-100 md:group-hover:translate-y-0" x-cloak>
                            <ul class="flex flex-col justify-between mx-auto max-w-7xl md:px-16 md:flex-row">
                                <li class="w-full border-l border-gray-100 md:w-1/5">
                                    <a href="#_" onclick="demoButtonClickMessage(event)" class="block h-full p-6 text-lg font-semibold hover:bg-gray-50 lg:p-7 lg:py-10">
                                       <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-red-600">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                                        </svg>
                                        <span class="block my-2 text-xs font-bold uppercase text-slate-800">Local Government</span>
                                        <span class="block text-xs font-medium leading-5 text-slate-500">Integrated with LGUs for citizen safety programs</span>
                                    </a>
                                </li>
                                <li class="w-full border-l border-gray-100 md:w-1/5">
                                    <a href="#_"  class="block h-full p-6 text-lg font-semibold hover:bg-gray-50 lg:p-7 lg:py-10">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-red-600">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                                            </svg>
                                        <span class="block my-2 text-xs font-bold uppercase text-slate-800">Schools</span>
                                        <span class="block text-xs font-medium leading-5 text-slate-500">Student safety and emergency response systems</span>
                                    </a>
                                </li>
                                <li class="w-full border-l border-gray-100 md:w-1/5">
                                    <a href="#_"  class="block h-full p-6 text-lg font-semibold hover:bg-gray-50 lg:p-7 lg:py-10">
                                        <svg class="h-8 w-8 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:1;"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                                        <span class="block my-2 text-xs font-bold uppercase text-slate-800">Ride Hailing</span>
                                        <span class="block text-xs font-medium leading-5 text-slate-500">Enhanced safety for drivers and passengers</span>
                                    </a>
                                </li>
                                <li class="w-full border-l border-gray-100 md:w-1/5">
                                    <a href="#_"  class="block h-full p-6 text-lg font-semibold hover:bg-gray-50 lg:p-7 lg:py-10">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-8 w-8 text-red-600">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                        </svg>
                                        <span class="block my-2 text-xs font-bold uppercase text-slate-800">Logistics</span>
                                        <span class="block text-xs font-medium leading-5 text-slate-500">Fleet safety and driver emergency profiles</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li> -->
                    <li x-data="{ open: false }" @mouseenter="showOverlay=true" @mouseleave="showOverlay=false" class="z-30 flex flex-col items-start h-auto border-b border-gray-100 md:h-full md:border-b-0 group md:flex-row md:items-center">
                        <a href="#_" x-on:click="open=!open" class="flex items-center w-full h-16 gap-1 text-sm font-semibold text-gray-700 transition duration-300 hover:bg-gray-100 md:hover:bg-transparent px-7 md:h-full md:px-0 md:w-auto hover:text-gray-900">
                            <span class="">Features</span>
                            <svg :class="{ 'group-hover:-rotate-180' : !mobileMenuOpen, '-rotate-180' : mobileMenuOpen && open }" class="w-5 h-5 transition-all duration-300 ease-out" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" class=""></path></svg>
                        </a>
                        <div 
                            :class="{ 'hidden md:block opacity-0 invisible md:absolute' : !open, 'md:invisible md:opacity-0 md:hidden md:absolute' : open }"
                            class="top-0 left-0 w-screen space-y-3 transition-transform duration-300 ease-out bg-white border-t border-b border-gray-100 md:shadow-md md:-translate-y-2 md:mt-24 md:block md:group-hover:block md:group-hover:visible md:group-hover:opacity-100 md:group-hover:translate-y-0" x-cloak>
                            <ul class="flex flex-col justify-between mx-auto max-w-7xl md:flex-row md:px-12">
                                <div class="flex flex-col w-full border-l border-r divide-x md:flex-row divide-zinc-100 border-zinc-100">
                                    <div class="w-auto divide-y divide-zinc-100">
                                        <a href="/features"  class="block text-sm p-7 hover:bg-neutral-100 group">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-red-600">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />
                                            </svg>
                                            <span class="block mb-1 font-medium text-black">QR Code Profile</span>
                                            <span class="block font-light leading-5 opacity-50">Instant access to your emergency information via scannable QR code stickers</span>
                                        </a>
                                        <a href="/features"  class="block text-sm p-7 hover:bg-neutral-100 group">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-red-600">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                            </svg>
                                            <span class="block mb-1 font-medium text-black">NFC Cards</span>
                                            <span class="block leading-5 opacity-50">Tap-to-share emergency profiles with NFC-enabled cards and tags</span>
                                        </a>
                                    </div>
                                    <div class="w-auto divide-y divide-zinc-100">
                                        <a href="/features"  class="block text-sm p-7 hover:bg-neutral-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-red-600">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                            </svg>
                                            <span class="block mb-1 font-medium text-black">Health History</span>
                                            <span class="block font-light leading-5 opacity-50">Store medical conditions, allergies, and medications securely</span>
                                        </a>
                                        <a href="/features"  class="block text-sm p-7 hover:bg-neutral-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-red-600">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                            </svg>
                                            <span class="block mb-1 font-medium text-black">Emergency Contacts</span>
                                            <span class="block leading-5 opacity-50">Quick access to your emergency contacts when it matters most</span>
                                        </a>
                                    </div>
                                    <div class="w-auto divide-y divide-zinc-100">
                                        <a href="/features"  class="block text-sm p-7 hover:bg-neutral-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-red-600">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                            </svg>
                                            <span class="block mb-1 font-medium text-black">GPS Tracking</span>
                                            <span class="block leading-5 opacity-50">Real-time location tracking for added safety and peace of mind</span>
                                        </a>
                                        <a href="/features"  class="block text-sm p-7 hover:bg-neutral-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-red-600">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                            </svg>
                                            <span class="block mb-1 font-medium text-black">Crash Detection (Coming soon!)</span>
                                            <span class="block leading-5 opacity-50">Automatic emergency alerts when crashes or accidents are detected</span>
                                        </a>
                                    </div>
                                </div>
                            </ul>
                        </div>
                    </li>
                    <li class="flex-shrink-0 h-16 border-b border-gray-100 md:border-b-0 md:h-full">
                        <a href="/benefits" wire:navigate class="@if (request()->is('benefits')) font-semibold text-sm tracking-tight text-[#BC0100] border-b-2 border-[#BC0100] @endif flex items-center h-full text-sm font-semibold text-gray-700 transition duration-300 md:px-0 px-7 hover:bg-gray-100 md:hover:bg-transparent hover:text-gray-900">
                            Benefits
                        </a>
                    </li>
                    <li class="flex-shrink-0 h-16 border-b border-gray-100 md:border-b-0 md:h-full">
                        <a href="/how-it-works" wire:navigate class="@if (request()->is('how-it-works')) font-semibold text-sm tracking-tight text-[#BC0100] border-b-2 border-[#BC0100] @endif flex items-center h-full text-sm font-semibold text-gray-700 transition duration-300 md:px-0 px-7 hover:bg-gray-100 md:hover:bg-transparent hover:text-gray-900">
                            How it works?
                        </a>
                    </li>
                    <li class="flex-shrink-0 h-16 border-b border-gray-100 md:border-b-0 md:h-full">
                        <a href="{{ route('pricing') }}" wire:navigate class="@if (request()->is('pricing')) font-semibold text-sm tracking-tight text-[#BC0100] border-b-2 border-[#BC0100] @endif flex items-center h-full text-sm font-semibold text-gray-700 transition duration-300 md:px-0 px-7 hover:bg-gray-100 md:hover:bg-transparent hover:text-gray-900">
                            Pricing
                        </a>
                    </li>
                    <li class="flex-shrink-0 h-16 border-b border-gray-100 md:border-b-0 md:h-full">
                        <a href="/contact" wire:navigate class="@if (request()->is('contact')) font-semibold text-sm tracking-tight text-[#BC0100] border-b-2 border-[#BC0100] @endif flex items-center h-full text-sm font-semibold text-gray-700 transition duration-300 md:px-0 px-7 hover:bg-gray-100 md:hover:bg-transparent hover:text-gray-900">Contact Us</a>
                    </li>

                    @guest
                        <li class="relative z-30 flex flex-col items-center justify-center flex-shrink-0 w-full h-auto pt-3 space-y-3 text-sm md:hidden px-7">
                            <x-button href="{{ route('login') }}" tag="a" class="w-full text-sm" color="secondary">Login</x-button>
                            <x-button href="{{ route('register') }}" tag="a" class="w-full text-sm">Sign Up</x-button>
                        </li>
                    @else
                        <li class="flex items-center justify-center w-full pt-3 md:hidden px-7">
                            <x-button href="{{ route('login') }}" tag="a" class="w-full text-sm fi-bg-color-600">View Profile</x-button>
                        </li>
                    @endguest

                </ul>
            </nav>
            
            @guest
                <div class="relative z-30 items-center justify-center flex-shrink-0 hidden h-full space-x-3 text-sm md:flex">
                    <x-button href="{{ route('login') }}" tag="a" class="text-sm" color="secondary">Login</x-button>
                    <x-button href="{{ route('register') }}" tag="a" class="text-sm">Sign Up</x-button>
                </div>
            @else
                <x-button href="{{ route('login') }}" tag="a" class="text-sm" class="relative z-20 flex-shrink-0 hidden ml-2 md:block">View Profile</x-button>
            @endguest

        </div>
    </x-container>

</header>
