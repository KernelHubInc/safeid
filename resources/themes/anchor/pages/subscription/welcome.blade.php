<?php
    use function Laravel\Folio\{middleware, name};
    use Livewire\Volt\Component;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Str;
    name('subscription.welcome');
    middleware('auth');

    new class extends Component
    {
        public ?string $planName = null;          // e.g., Premium

        public function mount(): void
        {
            $user = Auth::user();

            if (!$user) {
                redirect()->to('/');
                return;
            }
                
            // Allowed pages when locked:
            $allowed = [
                'dashboard',
                'wave.subscription',     // <-- adjust to Wave's route name for billing/subscription page
                'safeid.subscription',   // if you created your own subscription page
            ];

            if ($user->emerionAccessLocked() && request()->route() && !request()->routeIs(...$allowed)) {
                redirect()->route('settings.subscription');
                return;
            }

            // FORCE setup wizard if not completed and not hidden (no middleware, per your request)
            if (
                !$user->safeid_hide_onboarding &&
                is_null($user->safeid_setup_completed_at)
            ) {
                redirect()->to('emergency-profile-setup');
                return;
            }

            // Plan name (MVP): from user column or method
            $planName = '';
            if ($user->hasRole('trial')) {
                $planName = 'Trial';
            } else if ($user->hasRole('solo')) {
                $planName = 'Solo';
            } else if ($user->hasRole('premium')) {
                $planName = 'Premium';
            }
            $this->planName = $planName;
        }
    };
?>

@volt('subscription.welcome')
<div>
<x-layouts.app>
	<x-app.container x-data class="space-y-6" x-cloak>
        <div class="w-full">
            <x-app.heading
                title="Successfully Purchased 🎉"
                description="Thanks for upgrading to a subscription plan."
            />
            <div class="min-h-screen flex items-center justify-center px-6">
                <div class="max-w-2xl w-full text-center">

                    <!-- Success Icon -->
                    <div class="flex justify-center mb-6">
                        <div class="bg-gray-500/10 p-6 rounded-full">
                            <img class="w-32" src="/storage/emerion-logo.png">
                        </div>
                    </div>

                    <!-- Title -->
                    <h1 class="text-3xl md:text-4xl font-bold mb-4">
                        Welcome to Emerion 🚀
                    </h1>

                    <!-- Subtitle -->
                    <p class="text-gray-400 text-lg mb-8">
                        Your subscription is now active. You're ready to protect and connect.
                    </p>

                    <!-- Plan Badge -->
                    <div class="inline-block bg-indigo-600/20 text-indigo-400 font-bold px-4 py-2 rounded-full text-lg mb-8">
                        {{ $planName }} Plan Activated
                    </div>

                    <!-- Next Steps -->
                    <div class="bg-white-900 border border-gray-300 rounded-2xl p-6 text-left mb-8">
                        <h2 class="text-xl font-semibold mb-4 text-gray-900">What to do next:</h2>

                        <ul class="space-y-4 text-gray-900">
                            <li class="flex items-start gap-3">
                                <span class="text-indigo-400">1.</span>
                                Complete your profile information
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-indigo-400">2.</span>
                                Add emergency contacts
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-indigo-400">3.</span>
                                Activate your QR / tracking features
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-indigo-400">4.</span>
                                Test scan your QR for verification
                            </li>
                        </ul>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col md:flex-row gap-4 justify-center">
                        <a href="/dashboard"
                        class="bg-indigo-600 hover:bg-indigo-700 transition px-6 py-3 rounded-xl font-semibold text-white">
                            Go to Dashboard
                        </a>

                        <a href="/emergency-profile"
                        class="bg-gray-800 hover:bg-gray-700 transition px-6 py-3 rounded-xl font-semibold text-white">
                            Complete Profile
                        </a>
                    </div>

                    <!-- Divider -->
                    <div class="my-10 border-t border-gray-800"></div>

                    <!-- Support -->
                    <p class="text-gray-500 text-sm">
                        Need help? Contact <a href="/contact">support</a> or check our guide to get started.
                    </p>

                </div>
            </div>
        </div>
    </x-app.container>
    <x-slot name="javascript">
        <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
        <script>
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        </script>
    </x-slot>
</x-layouts.app>
</div>
@endvolt