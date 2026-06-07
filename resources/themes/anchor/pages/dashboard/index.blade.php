<?php

use function Laravel\Folio\{middleware, name};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Models\EmergencyProfile;
use App\Models\EmergencyContact;
use App\Models\SafeAsset;
use Illuminate\Support\Facades\Storage;
use App\Services\QrPngGenerator;

name('dashboard');

new class extends Component
{
    public ?EmergencyProfile $profile = null;

    public int $profileCompletion = 0;
    public int $contactsCount = 0;

    public ?string $planName = null;          // e.g., Premium
    public ?string $lastUpdatedDate = null;   // formatted
    public ?SafeAsset $primaryAsset = null;   // QR token

    // Optional “live” widgets (placeholders until device integration)
    public bool $gpsActive = false;
    public ?string $gpsLastLocation = null;
    public ?string $gpsUpdatedText = null;

    public bool $crashActive = false;
    public ?string $crashStatusLine1 = null;
    public ?string $crashStatusLine2 = null;

	public bool $trialActive = false;
	public int $trialDaysLeft = 0;
	public ?string $trialEndsAt = null;
	public bool $locked = false;
	public bool $subscriptionActive = false;

	// QR
	public ?string $qrImageUrl = null;
	public bool $generatingQr = false;

	// profile
	public bool $isPublic = true;


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
			redirect()->route('settings.subscription')->send();
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

        $this->profile = $user->emergencyProfile()->first();

        // If profile missing, still allow dashboard but keep values empty
        if ($this->profile) {
            $this->contactsCount = EmergencyContact::where('profile_id', $this->profile->id)->count();

            // Get an active QR asset (sticker/card)
            $this->primaryAsset = SafeAsset::query()
                ->where('profile_id', $this->profile->id)
                ->whereNull('deactivated_at')
                ->orderByRaw("CASE WHEN type='qr_sticker' THEN 0 WHEN type='qr_card' THEN 1 ELSE 2 END")
                ->first();

            $this->lastUpdatedDate = $this->profile->updated_at
                ? $this->profile->updated_at->format('F j, Y')
                : null;

            $this->profileCompletion = $this->computeProfileCompletion();
			$this->isPublic = (bool) ($this->profile?->is_public ?? false);
			$this->refreshQrImageUrl();
        }

        // Widgets (MVP placeholder)
        $this->gpsActive = true;
        $this->gpsLastLocation = 'Last location: San Francisco, CA';
        $this->gpsUpdatedText = 'Updated 5 minutes ago';

        $this->crashActive = true;
        $this->crashStatusLine1 = 'No incidents detected';
        $this->crashStatusLine2 = 'System monitoring active';

				
		$this->trialActive = $user->emerionTrialActive();
		$this->trialDaysLeft = $user->emerionTrialDaysLeft();
		$this->trialEndsAt = $user->trial_ends_at?->format('F j, Y');
		$this->locked = $user->emerionAccessLocked();
		$this->subscriptionActive = $user->emerionHasActiveSubscription();

        // Plan name (MVP): from user column or method
		$planName = '';
		if ($user->hasRole('registered')) {
			if ($user->emerionTrialActive()) {
				$planName = 'Basic - Free Trial';
			} else{
				$planName = 'Free';
			}
		} else if ($user->hasRole('basic')) {
			$planName = 'Basic';
		} else if ($user->hasRole('solo')) {
			$planName = 'Solo';
		} else if ($user->hasRole('premium')) {
			$planName = 'Premium';
		}
        $this->planName = $planName;
    }

    protected function computeProfileCompletion(): int
    {
        // Simple scoring based on filled fields + at least 1 contact + some health info
        $p = $this->profile;
        if (!$p) return 0;

        $checks = [
            !empty($p->first_name),
            !empty($p->last_name),
            !empty($p->birthdate),
            !empty($p->blood_type),
            !empty($p->address_line),
            !empty($p->city),
            !empty($p->province),
            !empty($p->zip_code),

            // health info presence (any)
            !empty($p->allergies) || !empty($p->current_medications) || !empty($p->medical_conditions),

            // contacts
            $this->contactsCount > 0,
        ];

        $total = count($checks);
        $done = collect($checks)->filter()->count();

        return (int) round(($done / max($total, 1)) * 100);
    }

    public function qrScanUrl(): ?string
    {
        if (!$this->primaryAsset) return null;
        // return route('me', ['public_token' => $this->primaryAsset->public_token]);
		return env("APP_URL") . "/scan/{$this->primaryAsset->public_token}";
    }

    public function downloadQr()
	{
		if (!$this->primaryAsset || !$this->primaryAsset->qr_path) {
			$this->dispatch('toast', message: 'QR not available.');
			return;
		}

		$filePath = Storage::path($this->primaryAsset->qr_path);
		$fileName = 'emerion-qr-' . $this->primaryAsset->public_token . '.png';

		return response()->download($filePath, $fileName);
	}



    public function shareQr(): void
    {
        $url = $this->qrScanUrl();
        $this->dispatch('copy-to-clipboard', text: $url ?: '');
        $this->dispatch('toast', message: 'QR link copied.');
    }
	
	protected function refreshQrImageUrl(): void
	{
		$this->qrImageUrl = null;

		if ($this->primaryAsset?->qr_path) {
			$this->qrImageUrl = Storage::url($this->primaryAsset->qr_path) . '?v=' . time();
		}
	}

	public function generateQrNow(): void
	{
		if (!$this->primaryAsset) {
			$this->dispatch('toast', message: 'No active asset found to generate QR.');
			return;
		}

		// If already generated, just show it
		if ($this->primaryAsset->qr_path) {
			$this->refreshQrImageUrl();
			$this->dispatch('toast', message: 'QR already generated.');
			return;
		}

		$this->generatingQr = true;

		try {
			// $url = route('me', ['public_token' => $this->primaryAsset->public_token]);
			$url = env('APP_URL') . '/scan/' . $this->primaryAsset->public_token;

			$pngBinary = app(QrPngGenerator::class)->make($url);

			// Store: storage/app/public/qrcodes/{token}.png
			$storagePath = "public/qrcodes/{$this->primaryAsset->public_token}.png";
			Storage::put($storagePath, $pngBinary);

			// Save public path for Storage::url()
			$this->primaryAsset->update([
				'qr_path' => $storagePath
			]);

			// Refresh instance + URL
			$this->primaryAsset->refresh();
			$this->refreshQrImageUrl();

			$this->dispatch('toast', message: 'QR generated successfully.');
		} finally {
			$this->generatingQr = false;
		}
	}

	public function toggleProfileVisibility(): void
	{
		if (!$this->profile) return;

		$this->isPublic = !$this->isPublic;

		$this->profile->update([
			'is_public' => $this->isPublic,
		]);

		$this->dispatch('toast', message: $this->isPublic ? 'Profile set to PUBLIC.' : 'Profile set to PRIVATE.');
	}
};
?>

@volt('dashboard')
<div>
    <x-layouts.app>
        <x-app.container x-data x-cloak>
			<div class="min-h-screen bg-gray-50">
				<div class="max-w-full mx-auto px-4 py-8">

					<!-- Header -->
					<div class="mb-6">
						<h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
						<p class="text-sm text-gray-600">Welcome back, {{ Auth::user()->name ?? 'User' }}</p>
					</div>

					<!-- Top stats -->
					<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
						<!-- Profile Status -->
						<div class="rounded-xl border bg-white p-5 shadow-sm">
							<div class="flex items-start justify-between">
								<div>
									<div class="text-sm font-medium text-gray-700">Profile Status</div>
									<div class="mt-2 text-2xl font-semibold text-gray-900">{{ $profileCompletion }}%</div>
									<div class="text-xs text-gray-500">Complete</div>
								</div>
								<div class="text-gray-400">
									<!-- icon -->
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
											d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
									</svg>
								</div>
							</div>

							<div class="mt-4 h-2 w-full rounded-full bg-gray-100 overflow-hidden">
								<div class="h-2 rounded-full bg-red-600 transition-all duration-300"
									style="width: {{ $profileCompletion }}%"></div>
							</div>
						</div>

						<!-- Contacts -->
						<div class="rounded-xl border bg-white p-5 shadow-sm">
							<div class="flex items-start justify-between">
								<div>
									<div class="text-sm font-medium text-gray-700">Emergency Contacts</div>
									<div class="mt-2 text-2xl font-semibold text-gray-900">{{ $contactsCount }}</div>
									<div class="text-xs text-gray-500">Active contacts</div>
								</div>
								<div class="text-gray-400">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
											d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.128a11.042 11.042 0 005.516 5.516l1.128-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 16.72V20a2 2 0 01-2 2h-1C9.716 22 3 15.284 3 7V5z"/>
									</svg>
								</div>
							</div>
						</div>

						<!-- Last updated -->
						<div class="rounded-xl border bg-white p-5 shadow-sm">
							<div class="flex items-start justify-between">
								<div>
									<div class="text-sm font-medium text-gray-700">Last Updated</div>
									<div class="mt-2 text-sm font-semibold text-gray-900">
										{{ $lastUpdatedDate ?? '—' }}
									</div>
									<div class="text-xs text-gray-500">Profile info</div>
								</div>
								<div class="text-gray-400">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
											d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
									</svg>
								</div>
							</div>
						</div>
					</div>

					<div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
						<!-- Subscription -->
						<div class="rounded-xl border bg-white p-5 shadow-sm">
							<div class="flex items-start justify-between">
								<div>
									<div class="text-sm font-medium text-gray-700">Subscription</div>
									<div class="mt-2 text-xl font-semibold text-gray-900">{{ $planName ?? '—' }}</div>
									<div class="text-xs text-gray-500">Active plan</div>
								</div>
								<div class="text-gray-400">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
											d="M3 10h18M7 15h1m4 0h1m-9 4h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
									</svg>
								</div>
							</div>
						</div>

						<div class="rounded-xl border bg-white p-5 shadow-sm">
							<div class="flex items-start justify-between">
								<div>
									@if ($planName == 'Trial')
									<div class="text-sm font-medium text-gray-700">Trial</div>
										@if($trialActive)
											<div class="mt-2 text-xl font-semibold text-gray-900">
												{{ $trialDaysLeft }} day/s left
											</div>
											<div class="text-xs text-gray-500">
												Ends {{ $trialEndsAt }}
											</div>
										@else
											<div class="mt-2 text-xl font-semibold text-gray-900">Trial ended</div>
											<div class="text-xs text-gray-500">Subscribe to continue</div>
										@endif
									@else
									<div class="text-sm font-medium text-gray-700">{{ $planName }} Subscription</div>
										@if($subscriptionActive || $trialActive)
											<div class="mt-2 text-xl font-semibold text-gray-900">
												{{ $trialDaysLeft }} day/s left
											</div>
											<div class="text-xs text-gray-500">
												Ends {{ $trialEndsAt }}
											</div>
										@else
											<div class="mt-2 text-xl font-semibold text-gray-900">Subscription ended</div>
											<div class="text-xs text-gray-500">Subscribe to continue</div>
										@endif
									@endif

									
								</div>

								@if ($planName == 'Trial')
								<span class="rounded-full px-3 py-1 text-xs font-semibold
									{{ $trialActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
									{{ $trialActive ? 'Active' : 'Expired' }}
								</span>
								@else
								<span class="rounded-full px-3 py-1 text-xs font-semibold
									{{ $subscriptionActive || $trialActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
									{{ $subscriptionActive || $trialActive ? 'Active' : 'Expired' }}
								</span>
								@endif
							</div>

							@if($locked)
								<div class="mt-4">
									<a href="{{ route('settings.subscription') }}"
									class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
										Upgrade to continue
									</a>
								</div>
							@endif
						</div>

						<div class="rounded-xl border bg-white p-5 shadow-sm">
							<div class="flex items-start justify-between gap-4">
								<div>
									<div class="text-sm font-medium text-gray-700">Profile Visibility</div>
									<div class="mt-2 text-sm text-gray-600">
										@if($isPublic)
											Anyone can view your scan page.
										@else
											Only you (owner) can view your scan page.
										@endif
									</div>
								</div>

								<button type="button"
										wire:click="toggleProfileVisibility"
										class="relative inline-flex h-7 w-12 items-center rounded-full transition
											{{ $isPublic ? 'bg-green-600' : 'bg-gray-300' }}">
									<span class="inline-block h-5 w-5 transform rounded-full bg-white transition
												{{ $isPublic ? 'translate-x-6' : 'translate-x-1' }}"></span>
								</button>
							</div>

							<div class="mt-3 flex items-center justify-between">
								<span class="text-xs font-semibold {{ $isPublic ? 'text-green-700' : 'text-gray-600' }}">
									{{ $isPublic ? 'PUBLIC' : 'PRIVATE' }}
								</span>

								@if($this->qrScanUrl())
									<a class="text-xs font-semibold text-red-700 underline"
									href="{{ $this->qrScanUrl() }}" target="_blank">
										View scan page
									</a>
								@endif
							</div>
						</div>

					</div>

					<!-- Middle row: QR + Quick Actions -->
					<div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
						<!-- QR Code -->
						<div class="rounded-xl border bg-white p-6 shadow-sm">
							<div class="text-sm font-semibold text-gray-900">Your QR Code</div>
							<div class="text-sm text-gray-600">Scan to access your emergency profile</div>

							<div class="mt-5 flex items-center justify-center">
								<div class="rounded-xl border bg-white p-4">
									@if($qrImageUrl)
										<img src="{{ $qrImageUrl }}" alt="SafeID QR Code"
											class="h-56 w-56 rounded-lg border bg-white object-contain" />
									@else
										<div class="h-56 w-56 rounded-lg bg-gray-50 flex flex-col items-center justify-center text-xs text-gray-600 text-center px-6">
											<div class="font-semibold text-gray-800">QR not generated yet</div>
											<div class="mt-1">Click below to generate it now.</div>

											<button type="button"
													wire:click="generateQrNow"
													wire:loading.attr="disabled"
													class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60">
												<svg wire:loading class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
													<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
													<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
												</svg>
												<span wire:loading.remove>Generate QR</span>
												<span wire:loading>Generating...</span>
											</button>
										</div>
									@endif
								</div>
							</div>

							<div class="mt-4 text-center text-xs text-gray-500">
								ID: {{ $primaryAsset?->id ? 'USER' . str_pad((string) $primaryAsset->id, 6, '0', STR_PAD_LEFT) : '—' }}
							</div>

							<div class="mt-4 flex gap-3">
								<button type="button"
										wire:click="downloadQr"
										class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
									Download
								</button>
								<button type="button"
										wire:click="shareQr"
										class="w-full rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
									Share
								</button>
							</div>
						</div>

						<!-- Quick Actions -->
						<div class="rounded-xl border bg-white p-6 shadow-sm">
							<div class="text-sm font-semibold text-gray-900">Quick Actions</div>
							<div class="text-sm text-gray-600">Manage your emergency profile</div>

							<div class="mt-5 grid grid-cols-2 gap-4">
								<a href="/emergency-profile"
								class="rounded-xl border border-gray-200 bg-white p-6 text-center hover:bg-gray-50">
									<div class="text-lg">👤</div>
									<div class="mt-2 text-sm font-semibold text-gray-900">Update Profile</div>
								</a>

								<a href="/emergency-contact"
								class="rounded-xl border border-gray-200 bg-white p-6 text-center hover:bg-gray-50">
									<div class="text-lg">📞</div>
									<div class="mt-2 text-sm font-semibold text-gray-900">Emergency Contacts</div>
								</a>

								<a href="/health-information"
								class="rounded-xl border border-gray-200 bg-white p-6 text-center hover:bg-gray-50">
									<div class="text-lg">❤️</div>
									<div class="mt-2 text-sm font-semibold text-gray-900">Health Information</div>
								</a>

								<a href="/settings/subscription"
								class="rounded-xl border border-gray-200 bg-white p-6 text-center hover:bg-gray-50">
									<div class="text-lg">💳</div>
									<div class="mt-2 text-sm font-semibold text-gray-900">Subscription</div>
								</a>

								@if (auth()->user()->hasTeamAccess())
								<a href="/group"
								class="rounded-xl border border-gray-200 bg-white p-6 text-center hover:bg-gray-50">
									<div class="text-lg">👥</div>
									<div class="mt-2 text-sm font-semibold text-gray-900">Group Management</div>
								</a>
								@endif
							</div>
						</div>
					</div>

					<!-- Bottom row: GPS + Crash -->
					<!-- <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
						
						<div class="rounded-xl border bg-white p-6 shadow-sm">
							<div class="flex items-start justify-between">
								<div>
									<div class="text-sm font-semibold text-gray-900">GPS Tracking</div>
									<div class="text-sm text-gray-600">Your location tracking status</div>
								</div>
								<span class="rounded-full {{ $gpsActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }} px-3 py-1 text-xs font-semibold">
									{{ $gpsActive ? 'Active' : 'Inactive' }}
								</span>
							</div>

							<div class="mt-5 text-sm text-gray-700 space-y-2">
								<div class="font-medium text-gray-900">Location Services</div>
								<div>📍 {{ $gpsLastLocation ?? 'No location yet' }}</div>
								<div class="text-xs text-gray-500">{{ $gpsUpdatedText ?? '' }}</div>
							</div>
						</div>

						
						<div class="rounded-xl border bg-white p-6 shadow-sm">
							<div class="flex items-start justify-between">
								<div>
									<div class="text-sm font-semibold text-gray-900">Crash Detection</div>
									<div class="text-sm text-gray-600">Automatic emergency alerts</div>
								</div>
								<span class="rounded-full {{ $crashActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }} px-3 py-1 text-xs font-semibold">
									{{ $crashActive ? 'Active' : 'Inactive' }}
								</span>
							</div>

							<div class="mt-5 text-sm text-gray-700 space-y-2">
								<div class="font-medium text-gray-900">Detection Status</div>
								<div>ℹ️ {{ $crashStatusLine1 ?? '—' }}</div>
								<div class="text-xs text-gray-500">{{ $crashStatusLine2 ?? '' }}</div>
							</div>
						</div>
					</div> -->
				</div>

				<script>
					window.addEventListener('copy-to-clipboard', (e) => {
						const text = e.detail?.text || '';
						if (!text) return;
						navigator.clipboard?.writeText(text);
					});
					window.addEventListener('toast', (e) => console.log(e.detail?.message || 'OK'));
				</script>
			</div>
		</x-app.container>
    </x-layouts.app>
</div>
@endvolt