<?php
    use function Laravel\Folio\{name};
    name('benefits');
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
        .editorial-shadow {
            shadow-[0_12px_32px_rgba(27,28,28,0.06)]
        }
        .glass-header {
            background: rgba(251, 249, 248, 0.8);
            backdrop-filter: blur(20px);
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
    </style>
    
    <main class="pt-32 pb-20">
<!-- Hero Section -->
<section class="max-w-7xl mx-auto px-8 mb-24">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-end">
        <div class="lg:col-span-8">
            <h1 class="text-5xl md:text-7xl font-headline font-extrabold text-on-surface tracking-tight leading-[1.1] mb-8">
    Your Life-Saving Identity <br/>in moments of <span class="text-primary">emergency.</span>
</h1>
        </div>
        <div class="lg:col-span-4 pb-2">
            <p class="text-secondary text-lg leading-relaxed border-l-2 border-outline-variant pl-6">
                Emerion bridges the gap between emergencies and response—giving first responders instant access to your identity, medical data, and emergency contacts when every second matters.
            </p>
        </div>
    </div>
</section>
<!-- Main Benefits Bento Grid -->
<section class="max-w-7xl mx-auto px-8 space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Benefit Card 1: Families -->
        <div class="lg:col-span-2 group relative overflow-hidden rounded-[1.5rem] bg-surface-container-lowest shadow-[0_12px_32px_rgba(27,28,28,0.04)] hover:shadow-xl transition-all duration-500">
            <div class="flex flex-col md:flex-row h-full">
                <div class="p-10 flex flex-col justify-between flex-1">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-6">
                            <span class="material-symbols-outlined text-primary" data-icon="family_history" data-weight="fill">family_history</span>
                        </div>
                        <h3 class="text-3xl font-headline font-bold mb-4 tracking-tight">Peace of Mind for Families</h3>
                        <p class="text-secondary leading-relaxed mb-6">
                            Protect your family with instant access to their emergency identity. Whether it's a child, elderly parent, or loved one—responders can immediately see critical details and contact you fast.
                        </p>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm font-semibold text-on-surface">
                            <span class="material-symbols-outlined text-primary text-lg" data-icon="check_circle">check_circle</span>
                            Emergency Contact Priority Access
                        </div>
                        <div class="flex items-center gap-3 text-sm font-semibold text-on-surface">
                            <span class="material-symbols-outlined text-primary text-lg" data-icon="check_circle">check_circle</span>
                            Critical Medical Info Visibility
                        </div>
                    </div>
                </div>
                <div class="md:w-2/5 relative h-64 md:h-auto overflow-hidden">
                    <img alt="Peace of Mind for Families" class="absolute inset-0 w-full h-full object-cover grayscale-[20%] group-hover:scale-105 transition-transform duration-700" data-alt="a heartwarming moment of a multigenerational family sharing a meal in a sunlit garden, soft focus and warm tones" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDW8dZ6yx9tzwVeRN_sblMKtcup93ts6JCJfW2tPK4p6qpMK1okhDikDKT9G3QATaNRpak48mkXfF38mDofc2k5jJYbVWZZe8IKqZwkXZovCLfhHtrarUrToaMIRzj48qjrRtxP5pUq_EQoy3VvbOkggSeXclwS0LDXA0dFH6YGVENnHDcVfWQDQI0c3uJ-zjTpAfB1qa8wc1MTbdRt8QLQ4x8to4fJvcqiPyW0RGki2dJ5qajUXUjuVgY-O0id91ZuISt_MJXMfGpo"/>
                    <div class="absolute inset-0 bg-gradient-to-r from-surface-container-lowest via-transparent to-transparent hidden md:block"></div>
                </div>
            </div>
        </div>
        <!-- Benefit Card 2: Worldwide -->
        <div class="group relative overflow-hidden rounded-[1.5rem] bg-surface-container-low p-10 flex flex-col justify-between shadow-[0_12px_32px_rgba(27,28,28,0.04)]">
            <div>
                <div class="w-12 h-12 rounded-xl bg-primary-container/20 flex items-center justify-center mb-8">
                    <span class="material-symbols-outlined text-primary" data-icon="public">public</span>
                </div>
                <h3 class="text-2xl font-headline font-bold mb-4 tracking-tight">Worldwide Protection</h3>
                <p class="text-secondary leading-relaxed">
                    Emerion works anywhere in the world. As long as a smartphone can scan a QR code or tap NFC, your emergency profile is instantly accessible—no apps required.
                </p>
            </div>
            <div class="mt-8 pt-8 border-t border-outline-variant/30">
                <div class="text-xs font-bold text-primary uppercase tracking-tighter mb-2">Current Active Zones</div>
                <div class="flex -space-x-2">
                    <div class="w-8 h-8 rounded-full border-2 border-surface bg-zinc-200 flex items-center justify-center text-[10px] font-bold">EU</div>
                    <div class="w-8 h-8 rounded-full border-2 border-surface bg-zinc-200 flex items-center justify-center text-[10px] font-bold">NA</div>
                    <div class="w-8 h-8 rounded-full border-2 border-surface bg-zinc-200 flex items-center justify-center text-[10px] font-bold">AS</div>
                    <div class="w-8 h-8 rounded-full border-2 border-surface bg-zinc-200 flex items-center justify-center text-[10px] font-bold">+12</div>
                </div>
            </div>
        </div>
        <!-- Benefit Card 3: First Responders -->
        <div class="lg:col-span-1 group relative overflow-hidden rounded-[1.5rem] bg-on-surface text-white p-10 flex flex-col shadow-xl">
            <div class="relative z-10 h-full flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center mb-8">
                        <span class="material-symbols-outlined text-white" data-icon="emergency_share" data-weight="fill">emergency_share</span>
                    </div>
                    <h3 class="text-2xl font-headline font-bold mb-4 tracking-tight">Instant Responder Access</h3>
                    <p class="text-zinc-400 leading-relaxed">
                        In critical moments, responders can instantly access your medical conditions, allergies, and emergency contacts—even if you are unconscious. No delays. No guesswork.
                    </p>
                </div>
                <div class="mt-8">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/20 text-primary-fixed border border-primary/30 text-xs font-bold uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        Live Sync Active
                    </div>
                </div>
            </div>
            <div class="absolute bottom-0 right-0 opacity-10 pointer-events-none translate-x-1/4 translate-y-1/4 scale-150">
                <span class="material-symbols-outlined text-[200px]" data-icon="medical_information">medical_information</span>
            </div>
        </div>
        <!-- Secondary Feature 4 -->
        <div class="lg:col-span-2 group relative overflow-hidden rounded-[1.5rem] bg-white border border-outline-variant/10 p-1 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-surface-container-low to-white">
            <div class="grid md:grid-cols-2 h-full">
                <div class="relative h-full overflow-hidden min-h-[300px]">
                    <img alt="Technology Integration" class="absolute inset-0 w-full h-full object-cover grayscale transition-transform duration-700 group-hover:scale-110" data-alt="a minimalist, high-end close-up of a sleek smartwatch displaying a simplified emergency health alert interface with clean typography" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCcx7KjMWr3VB0s6ANfZ86jI-xA04i84eqmPqLU8RC1eZrx9WF9-m_SEpxq-4ZnJP7jIGX4aGT_GvooFxYWtpKi0uzLNwheZYfA0btjbdI6DGRdEsaRg7JcN8kugBwVmZXYRaXC9cMIxQGRRFSZ4ipmU16MWOnYAol--mWJzv_kqdP1Q09PiUJr2KRE9228PL8hxPH49VRHlF8y9U14grQP9398UBvxu6WkgFfUhxhxoCavrpUujB7c972guLLevnLINR4WoyFiA9ab"/>
                </div>
                <div class="p-10 flex flex-col justify-center">
                    <h3 class="text-2xl font-headline font-bold mb-4 tracking-tight">The Digital Vault</h3>
                    <p class="text-secondary leading-relaxed mb-6">
                        Your data stays secure and under your control. Choose what information is visible during emergencies while keeping sensitive details private. Update your profile anytime.
                    </p>
                    <a class="text-primary font-bold text-sm flex items-center gap-2 group/link" href="#">
                        Learn about Encryption
                        <span class="material-symbols-outlined text-sm group-hover/link:translate-x-1 transition-transform" data-icon="arrow_forward">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="mt-32 mb-20 max-w-7xl mx-auto px-8">
    <div class="flex flex-col items-center text-center max-w-3xl mx-auto">
        <h2 class="text-4xl md:text-5xl font-headline font-bold tracking-tight mb-6">Be Ready Before Emergencies Happen.</h2>
        <p class="text-secondary text-lg mb-10 leading-relaxed">
            Set up your emergency profile in minutes. No apps. No delays. Just one scan when it matters most.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
            <a href="/register"><button class="px-8 py-4 bg-primary text-white rounded-xl font-headline font-bold text-lg shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all">Create Free Profile</button></a>
            <a href="/contact"><button class="px-8 py-4 bg-surface-container-highest text-on-surface rounded-xl font-headline font-bold text-lg hover:bg-surface-container-high transition-colors">Contact Us</button></a>
        </div>
    </div>
</section>
</main>
</x-layouts.marketing>
