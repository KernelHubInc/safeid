<!-- <section class="relative bg-gradient-to-b from-red-50 to-white py-20 sm:py-32">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-4xl mx-auto">
            <div class="flex justify-center mb-8">
                <img src="/storage/emerion-logo.png" alt="Emerion Logo" class="h-24 w-24 sm:h-32 sm:w-32">
            </div>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
                Your Emergency Profile,
                <span class="text-red-600"> Always With You</span>
            </h1>
            <p class="text-lg sm:text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                Emerion provides instant access to critical emergency information through QR codes and NFC cards. 
                Stay protected with GPS tracking and crash detection technology.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}">
                    <button class="px-8 py-3 text-lg font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">
                        Get Started Free
                    </button>
                </a>
                <a href="{{ route('login') }}">
                    <button class="px-8 py-3 text-lg font-medium text-gray-700 border border-gray-300 hover:bg-gray-50 rounded-md">
                        Sign In
                    </button>
                </a>
            </div>
        </div>
    </div>
</section> -->
<section class="max-w-7xl mx-auto px-8 mb-32 pt-32">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
        <div class="lg:col-span-7">
            <h1 class="font-headline text-5xl md:text-7xl font-extrabold text-on-surface leading-[1.1] tracking-tight mb-8">
                                    Your Emergency Profile, <span class="text-primary">Always With You</span>
            </h1>
            <p class="text-secondary text-lg md:text-xl max-w-xl mb-10 leading-relaxed">
                Emerion provides instant access to critical emergency information through QR codes and NFC cards. 
                Stay protected with GPS tracking and crash detection technology.
            </p>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="/register">
                    <button class="bg-surface-tint from-primary to-primary-container text-on-primary font-headline font-bold text-lg px-10 py-5 rounded-xl hover:shadow-lg transition-all active:scale-95">
                        Get Started Free
                    </button>
                </a>
                <a href="/how-it-works">
                <button class="bg-surface-container-high text-on-surface font-headline font-semibold text-lg px-10 py-5 rounded-xl hover:bg-surface-container-highest transition-all active:scale-95">
                    How it Works
                </button>
                </a>
            </div>
            </div>
        <div class="lg:col-span-5 relative">
            <div class="absolute -inset-4 bg-primary/5 rounded-[2.5rem] blur-3xl"></div>
            <div class="relative bg-surface-container-lowest rounded-[2.5rem] p-4 shadow-[0_32px_64px_rgba(0,0,0,0.08)] rotate-[5deg]">
                <img alt="" class="w-full h-auto rounded-[2rem]" data-alt="high-end smartphone mockup displaying a clean medical emergency profile with vital signs and QR code in a minimalist UI" src="/storage/scan1.jpg"/>
            </div>
        </div>
    </div>
</section>