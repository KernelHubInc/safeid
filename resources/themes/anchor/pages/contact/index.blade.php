<?php
    use function Laravel\Folio\{name};
    use Livewire\Volt\Component;
    name('contact');

    new class extends Component {

        public $name = '';
        public $email = '';
        public $subject = 'General Inquiry';
        public $message = '';

        public $success = false;

        protected function rules()
        {
            return [
                'name' => 'required|min:3',
                'email' => 'required|email',
                'subject' => 'required',
                'message' => 'required|min:10',
            ];
        }

        public function send()
        {
            $this->validate();

            // Send email
            Mail::raw(
                "Name: {$this->name}\nEmail: {$this->email}\nSubject: {$this->subject}\n\nMessage:\n{$this->message}",
                function ($mail) {
                    $mail->to('contact@emerion.tech')
                        ->replyTo($this->email, $this->name)
                        ->subject("Emerion Contact: {$this->subject}");
                }
            );

            // Reset form
            $this->reset(['name', 'email', 'subject', 'message']);

            $this->success = true;
        }
    };
?>
@volt('contact')
<div>
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
            box-shadow: 0 12px 32px rgba(27, 28, 28, 0.06);
        }
        .hero-gradient {
            background: linear-gradient(135deg, #BC0100 0%, #EB0000 100%);
        }
    </style>
    
    <main class="pt-32 pb-20 px-8 max-w-7xl mx-auto">
        <!-- Hero Section -->
        <section class="mb-24">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-end">
                <div class="lg:col-span-7">
                <span class="text-primary font-headline font-bold tracking-widest uppercase text-xs mb-4 block">Get in Touch</span>
                    <h1 class="text-6xl md:text-7xl font-headline font-extrabold tracking-tighter text-on-surface mb-8 leading-[1.1]">
                        We’re here when <br/><span class="text-primary">it matters most.</span>
                    </h1>
                </div>
                <div class="lg:col-span-5 pb-4">
                    <p class="text-secondary text-lg leading-relaxed max-w-md">
                        Whether you're looking for enterprise solutions or have a question about our emergency protocols, our team is ready to assist with guardian-level care.
                    </p>
                </div>
            </div>
        </section>
        <!-- Bento Grid Contact Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Contact Form Card -->
            <div class="lg:col-span-2 bg-surface-container-lowest rounded-[1.5rem] editorial-shadow p-10">
                <h2 class="text-2xl font-headline font-bold mb-8">Send a Message</h2>
                 @if ($success)
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                        Message sent successfully!
                    </div>
                @endif
                <form wire:submit.prevent="send" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-widest text-secondary ml-1">Name</label>
                            <input wire:model="name" class="w-full bg-surface-container-high border-none rounded-xl py-4 px-5 focus:ring-2 focus:ring-primary/20 transition-all placeholder:text-secondary/50" placeholder="John Doe" type="text"/>
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-widest text-secondary ml-1">Email</label>
                            <input wire:model="email" class="w-full bg-surface-container-high border-none rounded-xl py-4 px-5 focus:ring-2 focus:ring-primary/20 transition-all placeholder:text-secondary/50" placeholder="john@example.com" type="email"/>
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-widest text-secondary ml-1">Subject</label>
                        <select wire:model="subject" class="w-full bg-surface-container-high border-none rounded-xl py-4 px-5 focus:ring-2 focus:ring-primary/20 transition-all text-secondary">
                            <option>General Inquiry</option>
                            <option>Technical Support</option>
                            <option>Partnership Opportunities</option>
                            <option>Enterprise Sales</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-widest text-secondary ml-1">Message</label>
                        <textarea wire:model="message" class="w-full bg-surface-container-high border-none rounded-xl py-4 px-5 focus:ring-2 focus:ring-primary/20 transition-all placeholder:text-secondary/50" placeholder="How can we help you?" rows="5"></textarea>
                        @error('message') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <button class="hero-gradient w-full md:w-auto px-12 py-4 rounded-xl font-headline font-bold text-white scale-100 hover:scale-[1.02] active:scale-95 transition-all shadow-lg shadow-primary/20">
                        Send Secure Message
                    </button>
                </form>
            </div>
            <!-- Sidebar Info Column -->
            <div class="space-y-8">
                <!-- Support Card -->
                <div class="bg-primary-container p-8 rounded-[1.5rem] text-white">
                    <span class="material-symbols-outlined text-4xl mb-4" data-icon="emergency_share" data-weight="fill">emergency_share</span>
                    <h3 class="text-xl font-headline font-bold mb-2">24/7 Support</h3>
                    <p class="text-white/80 text-sm mb-6 leading-relaxed">For immediate technical assistance regarding active safety protocols.</p>
                    <a class="font-bold text-lg hover:underline decoration-2 underline-offset-4" href="mailto:contact@emerion.com">contact@emerion.com</a>
                </div>
                <!-- Office Card -->
                <div class="bg-surface-container-low p-8 rounded-[1.5rem]">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-secondary mb-6">Headquarters</h3>
                    <div class="flex gap-4 mb-6">
                        <span class="material-symbols-outlined text-primary" data-icon="location_on">location_on</span>
                        <address class="not-italic text-on-surface font-medium">
                            Tower 8 Unit 712 Trees Residence<br/>
                            Quezon City, Metro Manila<br/>
                            Philippines
                        </address>
                    </div>
                    <div class="h-40 w-full rounded-xl overflow-hidden relative">
                        <img class="w-full h-full object-cover grayscale opacity-80" data-alt="Stylized map showing San Francisco urban grid in minimal grey and white tones with a single red marker" data-location="San Francisco" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCs9hYHpAm9rWRagNEcXSO1JKxn_5y8I8W_7rxDODJl88m6ClvKBQHyMkxrfnlTZJAse7ivP97O0fkwXthRNOaBg4S4Bwu3cQhY6egsioJAnYXvSayZD86wlJMeHl7_ckIZACnIhYyctu4trhX3O68ir75M_EqGaoVAcUzJn0Q1cXT-SiAGBCVGvLQM5Z6XtGgfRIRGoHeNjmfZjsW30PHonBBBF4cmajoG7WCsK2YZRXx_EBc_xAY1YgLy_nWUWaUXtpegFgexJcbp"/>
                        <div class="absolute inset-0 bg-primary/5"></div>
                    </div>
                </div>
                <!-- Social Links Card -->
                <div class="bg-surface-container-lowest editorial-shadow p-8 rounded-[1.5rem]">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-secondary mb-6">Follow Our Mission</h3>
                    <div class="flex flex-wrap gap-4">
                        <a class="w-12 h-12 rounded-xl bg-surface-container-high flex items-center justify-center text-on-surface hover:bg-primary hover:text-white transition-all" href="#">
                            <span class="material-symbols-outlined" data-icon="share">share</span>
                        </a>
                        <a class="w-12 h-12 rounded-xl bg-surface-container-high flex items-center justify-center text-on-surface hover:bg-primary hover:text-white transition-all" href="#">
                            <span class="material-symbols-outlined" data-icon="public">public</span>
                        </a>
                        <a class="w-12 h-12 rounded-xl bg-surface-container-high flex items-center justify-center text-on-surface hover:bg-primary hover:text-white transition-all" href="#">
                            <span class="material-symbols-outlined" data-icon="hub">hub</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Secondary Locations / Global Impact Section -->
        <section class="mt-20">
            <div class="bg-surface-container-low rounded-[1.5rem] p-12 grid grid-cols-1 md:grid-cols-3 gap-12">
                <div>
                    <h4 class="font-headline font-bold text-lg mb-2">Global Response</h4>
                    <p class="text-secondary text-sm">Strategic centers across 4 continents ensuring sub-second latency.</p>
                </div>
                <div>
                    <h4 class="font-headline font-bold text-lg mb-2">Media Inquiries</h4>
                    <p class="text-secondary text-sm">press@emerion.com for all journalistic and broadcast requests.</p>
                </div>
                <div>
                    <h4 class="font-headline font-bold text-lg mb-2">Careers</h4>
                    <p class="text-secondary text-sm">Join the team building the future of human safety at emerion.com/careers.</p>
                </div>
            </div>
        </section>
    </main>
</x-layouts.marketing>
</div>
@endvolt
