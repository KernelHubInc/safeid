<?php
    use function Laravel\Folio\{name};
    name('features');
?>

<x-layouts.marketing
    :seo="[
        'title'         => setting('site.title', 'Emerion'),
        'description'   => setting('site.description', 'Emerion'),
        'image'         => url('/og_image.png'),
        'type'          => 'website'
    ]"
>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .text-gradient-primary {
            background: linear-gradient(135deg, #bc0100 0%, #eb0000 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
    
    <main class="pt-20">
        <!-- Hero Section -->
        <section class="relative overflow-hidden pt-24 pb-16 px-8">
            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-7">
                    <h1 class="text-5xl md:text-7xl font-extrabold font-headline tracking-tighter text-on-surface leading-[1.1] mb-6">
                        Your Life-Saving Profile,<br><span class="text-gradient-primary">Ready in One Scan.</span>.
                    </h1>
                    <p class="text-secondary text-lg md:text-xl max-w-2xl leading-relaxed mb-10">
                        Emerion is a smart emergency identity platform that gives first responders instant access to your medical information, emergency contacts, and critical data through a simple QR scan or NFC tap. No app required—just fast, life-saving access when it matters most.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="/login">
                            <button class="bg-primary text-on-primary px-8 py-4 rounded-xl font-headline font-bold text-base transition-all hover:opacity-90 shadow-xl shadow-primary/25">
                                Explore Platform
                            </button>
                        </a>
                    </div>
                </div>
                <div class="lg:col-span-5 relative">
                    <div class="rounded-[2.5rem] overflow-hidden shadow-2xl transform rotate-3 hover:rotate-0 transition-transform duration-700">
                        <img class="w-full h-[500px] object-cover" data-alt="Modern medical emergency response center with professional staff monitoring digital safety interfaces in a high-tech environment" src="/storage/banner/herobanner.jpg"/>
                    </div>
                    <div class="absolute -bottom-6 -left-6 bg-surface-container-lowest p-6 rounded-2xl shadow-xl border border-outline-variant/10 max-w-[240px]">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-primary" data-icon="verified_user">verified_user</span>
                            <span class="text-xs font-bold uppercase tracking-widest text-secondary">Active System</span>
                        </div>
                        <p class="text-sm font-headline font-semibold text-on-surface">Emergency Profile Ready Anytime</p>
                    </div>
                </div>
            </div>
        </section>
        <!-- Bento Grid Features -->
        <section class="py-24 px-8 bg-surface-container-low">
            <div class="max-w-7xl mx-auto">
                <div class="mb-16">
                    <h2 class="text-3xl md:text-4xl font-extrabold font-headline tracking-tight text-on-surface mb-4">Life-Saving Features, Built for Emergencies</h2>
                    <p class="text-secondary max-w-xl">A complete emergency identity system designed to give instant access to your medical information, emergency contacts, and critical data through QR codes and NFC technology.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- QR Code Profiles -->
                    <div class="md:col-span-2 bg-surface-container-lowest rounded-3xl p-10 relative overflow-hidden group">
                        <div class="relative z-10 h-full flex flex-col justify-between">
                            <div>
                                <span class="material-symbols-outlined text-primary text-4xl mb-6" data-icon="qr_code_2">qr_code_2</span>
                                <h3 class="text-2xl font-bold font-headline mb-4">QR Code Profiles</h3>
                                <p class="text-secondary max-w-md leading-relaxed">Your personal emergency QR code profile provides instant access to medical conditions, allergies, and emergency contacts. Designed for accidents and critical situations, responders can scan and access life-saving information in seconds—no login or app required.</p>
                            </div>
                            <div class="mt-12 flex items-center gap-4">
                            </div>
                        </div>
                        <div class="absolute right-0 bottom-0 w-1/3 opacity-10 group-hover:opacity-20 transition-opacity">
                            <span class="material-symbols-outlined text-[200px]" data-icon="qr_code_scanner">qr_code_scanner</span>
                        </div>
                    </div>
                    <!-- Health History -->
                    <div class="bg-primary rounded-3xl p-10 text-on-primary flex flex-col justify-between hover:scale-[1.02] transition-transform cursor-pointer shadow-lg shadow-primary/20">
                        <div>
                            <span class="material-symbols-outlined text-4xl mb-6" data-icon="health_and_safety" style="font-variation-settings: 'FILL' 1;">health_and_safety</span>
                            <h3 class="text-2xl font-bold font-headline mb-4">Health History</h3>
                            <p class="text-on-primary/80 text-sm leading-relaxed">Store your medical history, medications, allergies, and critical conditions in a secure emergency profile. This ensures accurate and immediate treatment decisions during emergencies.
                        </div>
                        <!-- <div class="mt-8 pt-8 border-t border-on-primary/10">
                            <span class="text-xs font-bold uppercase tracking-widest opacity-70">DPA Compliant</span>
                        </div> -->
                    </div>
                    <!-- NFC Cards -->
                    <div class="bg-surface-container-lowest rounded-3xl p-10 flex flex-col justify-between shadow-sm hover:shadow-md transition-shadow group">
                        <div>
                            <span class="material-symbols-outlined text-primary text-4xl mb-6" data-icon="nfc">nfc</span>
                            <h3 class="text-2xl font-bold font-headline mb-4">NFC Cards</h3>
                            <p class="text-secondary text-sm leading-relaxed">Emerion NFC emergency cards allow instant access to your profile with a simple tap. Ideal for offline situations, responders can retrieve your emergency data even without scanning a QR code.</p>
                        </div>
                        <!-- <div class="mt-8 rounded-2xl overflow-hidden grayscale group-hover:grayscale-0 transition-all duration-500">
                            <img class="w-full h-40 object-cover" data-alt="Minimalist design of a sleek titanium smart card on a clean white background with elegant lighting" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAF4NaQwxg9gB1_VjgXitWfVSyvSSE1QZfnwGXjbRGiW9JYEgPf3gyfqCc_0T3WsZHWV33BXl4TwhCn4YEO8bBCyEKhqnIkG5eHjtEOOnhL0oulTzJ9LFOrjcpzLnMYwYi74yfcUOMCtIHytXrCF-Vr7onSJ5kDQsjfX5coEi69K_sFhqFdQQAsP_JHtQQACQvtYiHDTZRww22s9usX7KFMrz2omlPOo_cDm0TVE9R6RgfN8iKiNzl4QbXCHa-DCwXl0Rt1oBXG7k5D"/>
                        </div> -->
                    </div>
                    <!-- GPS & Contacts Combined Row -->
                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Emergency Contacts -->
                        <div class="bg-surface-container-lowest rounded-3xl p-8 shadow-sm">
                            <span class="material-symbols-outlined text-primary text-3xl mb-4" data-icon="contact_emergency">contact_emergency</span>
                            <h3 class="text-xl font-bold font-headline mb-3">Intelligent Contacts</h3>
                            <p class="text-secondary text-sm leading-relaxed">Automatically notify your emergency contacts during critical situations. Your family or guardians can be reached instantly with your essential information when needed most.</p>
                        </div>
                        <!-- GPS Tracking -->
                        <div class="bg-surface-container-lowest rounded-3xl p-8 shadow-sm">
                            <span class="material-symbols-outlined text-primary text-3xl mb-4" data-icon="location_on">location_on</span>
                            <h3 class="text-xl font-bold font-headline mb-3">GPS Tracking</h3>
                            <p class="text-secondary text-sm leading-relaxed">Future-ready GPS integration enables location-based emergency response. Designed for faster assistance and improved safety during accidents and critical events.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Featured Section: Crash Detection -->
        <section class="py-24 px-8 bg-on-surface">
            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="order-2 lg:order-1">
                    <div class="aspect-square rounded-[3rem] overflow-hidden bg-zinc-900 border border-white/5 relative">
                        <img class="w-full h-full object-cover opacity-60" data-alt="Abstract visualization of data telemetry lines and motion sensors in a dark high-tech artistic style" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA6zH5NZthVtuh_2xNIA2bUIOp3XELYq2JUMNg5h3B_9mgz6GsI4BHS8LiBV9aBFufNmcfX7ORGcaeIWn5gzRGAfiGHIbg_fgoA_nPuGqfwNB0E3KPkOTu2V0GBV39i1KWitIGN6J6W05DLeBdUDTcbjUP1PAg-L4Am_0lmlUjlMZ_MHP_djhkXGieQapZ0-o8YA51ZVNTduUMhOlDUxjacfI_YPmfyBiLZTNwUB_m8lQssO6Vg6la1_BLgwzQUWsFzz_Aw5b9x_kZo"/>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="w-64 h-64 border border-primary/30 rounded-full animate-ping absolute"></div>
                            <div class="w-48 h-48 border border-primary/50 rounded-full animate-pulse absolute"></div>
                            <div class="bg-primary/20 backdrop-blur-md border border-primary/50 p-8 rounded-full">
                                <span class="material-symbols-outlined text-white text-6xl" data-icon="collision" style="font-variation-settings: 'FILL' 1;">car_crash</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="order-1 lg:order-2">
                    
                    <h2 class="text-4xl md:text-5xl font-extrabold font-headline text-white mb-8 leading-tight">Crash Detection : <br/><span class="text-zinc-500">Instant Emergency Response.</span></h2>
                    <div class="space-y-8">
                        <div class="flex gap-6">
                            <div class="bg-white/5 p-3 h-fit rounded-xl">
                                <span class="material-symbols-outlined text-primary" data-icon="speed">speed</span>
                            </div>
                            <div>
                                <h4 class="text-white font-bold font-headline mb-2">Smart Crash Detection</h4>
                                <p class="text-zinc-400 text-sm leading-relaxed">Emerion is developing advanced crash detection technology that identifies accidents in real-time using motion and impact signals—designed to trigger emergency access instantly.
</p>
                            </div>
                        </div>
                        <div class="flex gap-6">
                            <div class="bg-white/5 p-3 h-fit rounded-xl">
                                <span class="material-symbols-outlined text-primary" data-icon="emergency_share">emergency_share</span>
                            </div>
                            <div>
                                <h4 class="text-white font-bold font-headline mb-2">Automated Emergency Alerts</h4>
                                <p class="text-zinc-400 text-sm leading-relaxed">In critical situations, Emerion will automatically notify your emergency contacts and provide instant access to your emergency profile—ensuring faster response when you cannot act.</p>
                            </div>
                        </div>
                        
                        <div class="flex gap-6">
                            <h1 class="text-white font-bold font-headline mb-2 text-3xl">Coming Soon!</h1>
                        </div>
                        </div>
                        
                    </div>
                   
                </div>
            </div>
        </section>
        <!-- CTA Section -->
        <section class="py-24 px-8">
            <div class="max-w-5xl mx-auto bg-surface-container-low rounded-[3rem] p-12 md:p-20 text-center relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-3xl md:text-5xl font-extrabold font-headline mb-6 tracking-tight">Be Ready Before Emergencies Happen.</h2>
                    <p class="text-secondary text-lg mb-10 max-w-2xl mx-auto">Create your emergency profile today and be prepared for accidents, medical emergencies, and unexpected situations. Fast setup. No app required.</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="/register"><button class="bg-primary text-on-primary px-10 py-5 rounded-2xl font-headline font-bold transition-all hover:scale-105">Create Free Profile</button></a>
                        <a href="/contact"><button class="bg-white text-on-surface px-10 py-5 rounded-2xl font-headline font-bold border border-outline-variant/30 transition-all hover:bg-surface-container-high">Contact Us</button></a>
                    </div>
                </div>
                <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/4 w-96 h-96 bg-primary/5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 translate-y-1/2 -translate-x-1/4 w-96 h-96 bg-primary/5 rounded-full blur-3xl"></div>
            </div>
        </section>
    </main>
</x-layouts.marketing>
