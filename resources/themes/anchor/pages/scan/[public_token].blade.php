<?php

use function Laravel\Folio\{middleware, name};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\TeamRegistrationService;
use App\Models\SafeAsset;
use App\Models\EmergencyProfile;
use App\Models\EmergencyContact;
use Livewire\Attributes\Rule;
use App\Models\User;
use Carbon\Carbon;
use App\Models\ScanLog;
use App\Models\MemberLocation;
use Illuminate\Validation\ValidationException;
use App\Services\GroqEmergencyAiService;
use App\Services\GroqVoiceReadoutService;

middleware('throttle:5,1'); // 5 requests per minute per IP
name('scan/{public_token}');

new class extends Component
{
    public string $public_token;

    public ?SafeAsset $asset = null;
    public ?EmergencyProfile $profile = null;
    public $contacts;

    public bool $assetNotFound = false;
    public bool $profileNotFound = false;
    public bool $isPrivateLocked = false;
    public bool $alreadyClaimed = false;
    public bool $claimedByYou = false;
    public bool $userHasProfile = false;
    public string $statusMessage = '';
    public string $statusTitle = '';
    public string $currentPlan = '';

    #[Rule('required|string|max:120')]
    public $name = "";
    
    #[Rule('required|email|max:190|unique:users,email')]
    public $email = "";
    
    #[Rule('required|string|min:8')]
    public $password = "";

    #[Rule('string|min:8')]
    public $claim_code = "";

    public $emailVerified = false;
    public $otp = '';
    public $otpCooldown = 0;
    public $toggleOtpAndVerificaion = false;

    // gps
    public ?float $lat = null;
    public ?float $lng = null;
    public ?string $location_text = null;

    public bool $locationSaved = false;
    
    public ?string $scanError = null;

    public $localEmergencyContact = '911';
    

    // AI summary
    public array $aiSummary = [];
    public bool $loadingAi = false;
    // AI TTS
    public ?string $voiceUrl = null;
    public ?string $voiceText = null;
    public bool $loadingVoice = false;
    

    public function mount(string $public_token): void
    {
        $user = Auth::user();
        if ($user && $user->emergencyProfile) {
            $this->userHasProfile = true;
        }

        $this->public_token = $public_token;

        $this->asset = SafeAsset::query()
            ->where('public_token', $public_token)
            ->whereNull('deactivated_at')
            ->first();

        if (!$this->asset) {
            $this->assetNotFound = true;
            $this->statusTitle = 'Asset not found';
            $this->statusMessage = 'Invalid or expired QR.';
            return;
        }

        $this->profile = EmergencyProfile::query()
            ->where('id', $this->asset->profile_id)
            ->first();

        if ($this->asset->owner_user_id && !$this->profile) {
            $this->profileNotFound = true;
            $this->statusTitle = 'Profile not found';
            $this->statusMessage = 'This QR token is invalid or has been deactivated.';
            return;
        }

        // Public/Private access control
        if ($this->profile && !$this->profile->is_public) {

            // allow if owner
            $ownerId = $this->profile->user_id ?? null; // adjust if your profile uses user_id
            $isOwner = $user && $ownerId && ((int) $user->id === (int) $ownerId);

            if (!$isOwner) {
                $this->isPrivateLocked = true;
                return;
            }
        }
        
        if ($this->asset && $this->asset->owner) {
            if ($this->asset->owner->hasRole(['registered', '', 'trial']) && !$this->asset->owner->emerionTrialActive()) {
                $this->currentPlan = 'registered';
            } else if ($this->asset->owner->hasRole(['basic']) || ($this->asset->owner->emerionTrialActive() && $this->asset->owner->hasRole(['registered', '', 'trial']))) {
                $this->currentPlan = 'basic';
            }
        }

        if ($this->asset->profile_id) {

            $this->contacts = EmergencyContact::query()
                ->where('profile_id', $this->profile->id)
                ->orderBy('priority') // 1 primary first
                ->orderBy('id')
                ->get();
            $this->alreadyClaimed = true;
            if ($this->asset->profile) {
                $this->claimedByYou = Auth::check() && Auth::id() === (int) $this->asset->profile->user_id;
            }

            $this->statusMessage = $this->claimedByYou
                ? ''
                : 'This QR is already registered to another account.';
            return;
        }

        $this->statusMessage = Auth::check() ? 'You have existing profile!' : 'Verify your email address and create an account to register this kit.';

        $this->statusTitle = Auth::check() ? '404 not found!' : 'Activate your kit now!';
        $this->syncOtpCooldown();
    }

    public function syncOtpCooldown(): void
    {
        if (blank($this->email)) {
            $this->otpCooldown = 0;
            return;
        }

        $this->otpCooldown = RateLimiter::availableIn($this->otpRateKey());
    }

    protected function otpRateKey(): string
    {
        return 'otp-' . strtolower(trim($this->email));
    }

    public function ageText(): ?string
    {
        if (!$this->profile?->birthdate) return null;
        try {
            return (string) $this->profile->birthdate->age;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function sendOtp(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'unique:users,email'],
        ]);

        $key = $this->otpRateKey();

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $this->otpCooldown = RateLimiter::availableIn($key);
            $this->addError('email', "Please wait {$this->otpCooldown} seconds before requesting another OTP.");
            return;
        }

        $otp = (string) random_int(100000, 999999);

        Cache::put(
            'email_otp_' . strtolower(trim($this->email)),
            $otp,
            now()->addMinutes(5)
        );

        Mail::raw("Your Emerion verification code is: {$otp}", function ($mail) {
            $mail->to($this->email)
                ->subject('Emerion Email Verification');
        });

        // 60-second resend interval
        RateLimiter::hit($key, 60);
        $this->otpCooldown = 60;
        $this->toggleOtpAndVerificaion = true;
    }

    public function verifyOtp(): void
    {
        $cacheKey = 'email_otp_' . strtolower(trim($this->email));
        $storedOtp = Cache::get($cacheKey);

        if (! $storedOtp) {
            $this->addError('otp', 'OTP expired.');
            return;
        }

        if ($storedOtp !== trim($this->otp)) {
            $this->addError('otp', 'Invalid OTP.');
            return;
        }

        $this->emailVerified = true;

        Cache::forget($cacheKey);
        RateLimiter::clear($this->otpRateKey());
        $this->otpCooldown = 0;
        $this->toggleOtpAndVerificaion = false;
    }

    public function refreshCooldown(): void
    {
        $this->syncOtpCooldown();
    }

    public function claim() {
        if ($this->assetNotFound) return;

        // ✅ Logged in: attach QR to this user
        if (Auth::check()) {
            return DB::transaction(function () {
                $asset = SafeAsset::query()
                    ->where('public_token', $this->public_token)
                    ->lockForUpdate()
                    ->first();

                if (!$asset) {
                    $this->statusMessage = 'Invalid claim code or expired QR.';
                    return;
                }

                if ($asset->claim_code) {
                    if ($this->claim_code !== $asset->claim_code) {
                        $this->statusMessage = 'Invalid claim code.';
                        return;
                    }
                }

                if ($asset->user_id) {
                    $this->alreadyClaimed = true;
                    $this->claimedByYou = Auth::id() === (int) $asset->user_id;
                    $this->statusMessage = $this->claimedByYou
                        ? 'This QR is already registered to your account.'
                        : 'This QR is already registered to another account.';
                    return;
                }

                $asset->update([
                    'owner_user_id' => Auth::id(),
                    'activated_at' => now(),
                    'registered_at' => now(),
                    'status' => 'registered',
                ]);

                $this->statusMessage = 'QR registered successfully!';
                return redirect()->to('/dashboard'); // change if needed
            });
        }

        // ✅ Not logged in: register user, login, attach QR
        $this->validate();

        $loggedInUser = DB::transaction(function () {
            $asset = SafeAsset::query()
                ->where('public_token', $this->public_token)
                ->lockForUpdate()
                ->first();

            if (! $asset) {
                $this->statusMessage = 'Invalid or expired QR.';
                return;
            }

            if ($asset->claim_code) {
                if ($this->claim_code !== $asset->claim_code) {
                    $this->statusMessage = 'Invalid claim code.';
                    return;
                }
            }

            if ($asset->user_id) {
                $this->alreadyClaimed = true;
                $this->statusMessage = 'This QR is already registered to another account.';
                return;
            }

            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password)
            ]);

            Auth::login($user);

            $this->statusMessage = 'Account created and QR registered!';

            return $user->load('emergencyProfile');
        });

        $asset = SafeAsset::query()->where('public_token', $this->public_token)->first();

        $provisioned = app(\App\Services\EmerionProvisioner::class)->provisionFor($loggedInUser, [
            'create_default_asset' => false,
            'generate_qr_png' => true,
        ]);

        if ($asset && $asset->batch) {
            $monthsToConvert = $asset->batch->validity ?? 0;
            $totalDays = 14;
            if ($monthsToConvert > 0) {
                $start = Carbon::now();
                $end = $start->copy()->addMonths($monthsToConvert);
                $totalDays = $start->diffInDays($end);
            }
            
            $loggedInUser->trial_ends_at = Carbon::now()->addDays($totalDays);
            $loggedInUser->save();            
        }

        if ($asset->kit_plan === 'premium' || $asset->kit_plan === 'enterprise') {
            app(TeamRegistrationService::class)->activateTeam($loggedInUser, $asset->kit_plan);
        } elseif ($asset->kit_plan === 'registered' || $asset->kit_plan === 'trial' || $asset->kit_plan === 'basic' || $asset->kit_plan === 'solo') {
            $loggedInUser->assignRole($asset->kit_plan);
        }

        $profile = $provisioned['profile'];
        $asset->update([
            'profile_id' => $profile->id,
            'owner_user_id' => $loggedInUser->id,
            'activated_at' => now(),
            'registered_at' => now(),
            'status' => 'registered',
        ]);
        
        return redirect()->to('/dashboard'); // change if needed
    }
    
    // save GPS location
    public function saveScanLocation(string $trigger = 'qr_scan')
    {
        if (! $this->profile) {
            abort(404);
        }

        if ($this->locationSaved) {
            return;
        }

        if (is_null($this->lat) || (!is_null($this->lat) && ($this->lat < -90 || $this->lat > 90)) ) {
            throw ValidationException::withMessages([
                'lat' => 'Invalid latitude.',
            ]);
        }

        if (is_null($this->lng) || (!is_null($this->lng) && ($this->lng < -180 || $this->lng > 180)) ) {
            throw ValidationException::withMessages([
                'lng' => 'Invalid longitude.',
            ]);
        }

        $ipAddress = request()->ip();

        if (!$this->canLogScanByIpForProfile($ipAddress, $this->profile->id)) {
            $this->scanError = 'Too many scan logs from this IP address for this profile. Only 3 logs are allowed every 15 minutes.';
            return redirect()->to('/');
        }

        $locationText = $this->location_text;

        if ((! $locationText || $locationText === 'GPS captured') && $this->lat && $this->lng) {
            $locationText = 'Lat: ' . $this->lat . ', Lng: ' . $this->lng;
        }

        ScanLog::create([
            'profile_id' => $this->profile->id,
            'asset_id' => $this->asset?->id,
            'scanned_by_user_id' => \Illuminate\Support\Facades\Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'lat' => $this->lat,
            'lng' => $this->lng,
            'location_text' => $locationText,
            'trigger' => $trigger,
        ]);

        if ($this->profile && $this->profile->user && $this->profile->user->team) {
            $teamId = $this->profile->user->team->id;
            $userId = $this->profile->user->id;
            $latest = MemberLocation::query()
                ->where('team_id', $teamId)
                ->where('user_id', $userId)
                ->latest('recorded_at')
                ->first();

            if (!$latest) {
                $latest = new MemberLocation();
                $latest->team_id = $teamId;
                $latest->user_id = $userId;
            }
            $latest->latitude = $this->lat;
            $latest->longitude = $this->lng;
            $latest->recorded_at = now();
            $latest->save();
        }

        $this->locationSaved = true;
    }

    protected function canLogScanByIpForProfile(?string $ipAddress, int $profileId): bool
    {
        if (blank($ipAddress)) {
            return true;
        }

        $count = ScanLog::query()
            ->where('profile_id', $profileId)
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        return $count < 3;
    }

    //end GPS

    // AI
    public function generateAiSummary(GroqEmergencyAiService $ai): void
    {
        $this->loadingAi = true;

        try {
            $data = [
                'blood_type' => $this->profile->blood_type,

                'allergies' => is_array($this->profile->allergies)
                    ? $this->profile->allergies
                    : (filled($this->profile->allergies) ? [$this->profile->allergies] : []),

                'current_medications' => is_array($this->profile->current_medications)
                    ? implode(', ', $this->profile->current_medications)
                    : $this->profile->current_medications,

                'medical_conditions' => is_array($this->profile->medical_conditions)
                    ? $this->profile->medical_conditions
                    : (filled($this->profile->medical_conditions) ? [$this->profile->medical_conditions] : []),

                'emergency_contacts' => $this->profile->contacts
                    ->map(function ($c) {
                        return [
                            'name' => $c->name,
                            'phone' => $c->phone,
                            'is_primary' => (bool) ($c->is_primary ?? false),
                        ];
                    })
                    ->values()
                    ->toArray(),
            ];

            $cacheKey = 'scan_ai_summary_' . md5(json_encode($data));

            $this->aiSummary = Cache::remember($cacheKey, now()->addHours(6), function () use ($ai, $data) {
                return $ai->summarize($data);
            });
        } catch (\Throwable $e) {
            $this->aiSummary = [
                'summary' => 'Unable to generate summary at the moment.',
                'alerts' => ['Please verify medical information manually.'],
            ];
        } finally {
            $this->loadingAi = false;
        }
    }

    public function generateVoiceReadout(GroqVoiceReadoutService $voice): void
    {
        if (empty($this->aiSummary)) {
            $this->generateAiSummary(app(GroqEmergencyAiService::class));
        }

        $this->loadingVoice = true;

        try {
            $result = $voice->generate($this->aiSummary, $this->public_token);

            $this->voiceUrl = $result['url'];
            $this->voiceText = $result['text'];
            $this->dispatch('play-voice');
        } catch (\Throwable $e) {
            $this->voiceUrl = null;
            $this->voiceText = null;
            dd($e->getMessage());
            $this->dispatch('notify', message: 'Unable to generate voice readout right now.');
           
        } finally {
            $this->loadingVoice = false;
        }
    }

    // END AI
};
?>

@volt('scan/{public_token}')
<div>
    <x-layouts.marketing>

        @if($assetNotFound)
            <div class="min-h-screen flex items-center justify-center px-4">
                <div class="w-full max-w-md rounded-2xl border bg-white p-6 shadow-sm text-center">
                    <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-red-100 flex items-center justify-center text-red-700">!</div>
                    <h1 class="text-lg font-semibold text-gray-900">{{ $statusTitle }}</h1>
                    <p class="mt-1 text-sm text-gray-600">{{ $statusMessage }}</p>
                </div>
            </div>
        @elseif($profileNotFound)
            <div class="min-h-screen flex items-center justify-center px-4">
                <div class="w-full max-w-md rounded-2xl border bg-white p-6 shadow-sm text-center">
                    <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-red-100 flex items-center justify-center text-red-700">!</div>
                    <h1 class="text-lg font-semibold text-gray-900">{{ $statusTitle }}</h1>
                    <p class="mt-1 text-sm text-gray-600">{{ $statusMessage }}</p>
                </div>
            </div>
        @elseif($isPrivateLocked)
            <div class="min-h-screen flex items-center justify-center px-4">
                <div class="w-full max-w-md rounded-2xl border bg-white p-6 shadow-sm text-center">
                    <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-red-100 flex items-center justify-center text-red-700">🔒</div>
                    <h1 class="text-lg font-semibold text-gray-900">Private Profile</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        This emergency profile is set to private. Please login as the owner to view it.
                    </p>

                    <div class="mt-5">
                        @if(Route::has('login'))
                            <a href="{{ route('login') }}"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                Login to View
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($alreadyClaimed)

            {{-- HEADER --}}
            <div class="bg-red-600">
                <div class="max-w-3xl mx-auto px-4 py-10 text-center text-white">
                    <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-white/15 flex items-center justify-center">
                        <img class="rounded-lg" src="/storage/emerion-logo2.png">
                    </div>

                    <h1 class="text-2xl font-bold">Emergency Profile</h1>
                    <p class="mt-1 text-sm text-white/90">This is an emergency medical information profile</p>

                    <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1 text-xs font-semibold">
                        Profile ID: {{ substr($asset->public_token, 0, 15) }}
                    </div>
                </div>
            </div>

            <div class="max-w-3xl mx-auto px-4 py-8 space-y-5">


                {{-- EMERGENCY SUMMARY --}}
                @if ($localEmergencyContact)
                <div class="rounded-2xl border border-red-200 bg-white shadow-sm">
                    <div class="rounded-t-2xl border-b border-red-100 bg-red-50 px-5 py-3">
                        <div class="flex items-center gap-2 text-xl font-semibold text-red-700">
                            <span>🚨</span> Emergency Summary
                        </div>
                        <p class="text-sm text-gray-500">Responder-friendly overview from the emergency profile.</p>
                    </div>

                    <div class="px-5 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-1 gap-4 text-sm">
                            <div>
                                <a href="tel:{{ $localEmergencyContact }}"
                                    class="inline-flex w-full items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-xl font-semibold text-white hover:bg-red-700 justify-center">
                                        📞 Call {{ $localEmergencyContact }} Now
                                    </a>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-2">
                        <div class="grid grid-cols-1 md:grid-cols-1 text-sm">
                            @if ($currentPlan !== "registered")
                                <div class="text-sm text-gray-700">
                                    @if(!empty($aiSummary))
                                        <div>
                                            <div>
                                                <h3 class="text-sm font-semibold text-gray-900">Emergency Summary:</h3>
                                                @if (is_string($aiSummary['summary']))
                                                <ul class="mt-2 list-disc pl-5 text-sm text-gray-700">
                                                    <li>{{ $aiSummary['summary'] ?? 'No summary available.' }}</li>
                                                </ul>
                                                @else
                                                <ul class="mt-2 list-disc pl-5 text-sm text-gray-700">
                                                    @forelse(($aiSummary['summary'] ?? []) as $summary)
                                                        <li>{{ $summary }}</li>
                                                    @empty
                                                        <li>No AI summary found.</li>
                                                    @endforelse
                                                </ul>
                                                @endif
                                            </div>

                                            <div>
                                                <h3 class="text-sm font-semibold text-gray-900">AI Alerts:</h3>
                                                <ul class="mt-2 list-disc pl-5 text-sm text-gray-700">
                                                    @forelse(($aiSummary['alerts'] ?? []) as $alert)
                                                        <li>{{ $alert }}</li>
                                                    @empty
                                                        <li>No critical AI alerts detected.</li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        </div>
                                    @else
                                        <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-1 text-sm text-gray-500 p-2">
                                            Tap <span class="font-medium text-gray-700">Generate</span> to create an AI emergency summary.
                                        </div>
                                        
                                        <button
                                            wire:click="generateAiSummary"
                                            class="bg-red-600 text-white w-full rounded mt-2 p-2"
                                            wire:loading.attr="disabled"
                                        >
                                            <span wire:loading.remove>Generate</span>
                                            <span wire:loading>Loading...</span>
                                        </button>
                                    @endif
                                </div>

                                <div class="text-sm whitespace-pre-line text-gray-700">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <h2 class="text-lg font-semibold text-blue-600">Rion Voice AI</h2>
                                            <p class="text-sm text-gray-500">Play a spoken emergency summary for the responder.</p>
                                        </div>

                                        @if(!$voiceUrl)
                                        <button
                                            type="button"
                                            wire:click="generateVoiceReadout"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60"
                                        >
                                            <span wire:loading.remove wire:target="generateVoiceReadout">Generate Voice</span>
                                            <span wire:loading wire:target="generateVoiceReadout">Generating Voice...</span>
                                        </button>
                                        @endif
                                    </div>

                                    @if($voiceUrl)
                                        <div>
                                            <audio id="rion-ai-voice" controls playsinline preload="metadata" autoplay class="w-full">
                                                <source src="{{ $voiceUrl }}" type="audio/wav">
                                                Your browser does not support audio playback.
                                            </audio>

                                            <!-- <div class="rounded-xl bg-gray-50 p-3 text-sm text-gray-600">
                                                <span class="font-medium text-gray-800">Spoken text:</span>
                                                {{ $voiceText }}
                                            </div> -->
                                        </div>
                                        <script>
                                        document.addEventListener('livewire:initialized', () => {
                                            Livewire.on('play-voice', () => {
                                                const audio = document.getElementById('rion-ai-voice');

                                                if (audio) {
                                                    audio.load();

                                                    const playPromise = audio.play();

                                                    if (playPromise !== undefined) {
                                                        playPromise.catch(error => {
                                                            console.log('Autoplay blocked:', error);
                                                        });
                                                    }
                                                }
                                            });
                                        });
                                        </script>
                                    @else
                                        <!-- <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4 text-sm text-gray-500">
                                            Generate voice to hear the AI emergency summary.
                                        </div> -->
                                    @endif
                                </div>                            
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- PERSONAL INFORMATION --}}
                <div class="bg-white rounded-2xl shadow-sm p-5 border border-red-200">
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a5 5 0 00-5 5v3H4a2 2 0 00-2 2v2h16v-2a2 2 0 00-2-2h-1V7a5 5 0 00-5-5z"/>
                        </svg>
                        <h2 class="text-lg font-semibold text-red-600">Personal Information</h2>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        
                        <!-- Name -->
                        <div class="bg-gray-50 rounded-xl p-4 border">
                            <p class="text-xs text-gray-500 mb-1">Name</p>
                            <p class="text-sm font-semibold text-gray-800">{{ trim(($profile->first_name ?? '')) ?: '—' }}</p>
                        </div>

                        <!-- Blood Type -->
                        <div class="bg-red-50 rounded-xl p-4 border border-red-200">
                            <p class="text-xs text-red-500 mb-1">Blood Type</p>
                            <p class="text-lg font-bold text-red-600">{{ $profile->blood_type ?? '—' }}</p>
                        </div>

                        <!-- Age -->
                        <div class="bg-gray-50 rounded-xl p-4 border">
                            <p class="text-xs text-gray-500 mb-1">Age</p>
                            <p class="text-sm font-semibold text-gray-800">
                                @if($profile->birthdate)
                                    @if($this->ageText()) {{ $this->ageText() }} years @endif
                                @else
                                    —
                                @endif
                            </p>
                        </div>

                        <!-- Height / Weight -->
                        <div class="bg-gray-50 rounded-xl p-4 border">
                            <p class="text-xs text-gray-500 mb-1">Height / Weight</p>
                            <p class="text-sm font-semibold text-gray-800">
                                {{ $profile->height_cm ? $profile->height_cm . ' cm' : '—' }}
                                    /
                                {{ $profile->weight_kg ? $profile->weight_kg . ' kg' : '—' }}
                            </p>
                        </div>

                    </div>
                </div>

                {{-- CONTACTS --}}
                <div class="rounded-2xl border border-red-200 bg-white shadow-sm">
                    <div class="rounded-t-2xl border-b border-red-100 bg-red-50 px-5 py-3">
                        <div class="flex items-center gap-2 text-sm font-semibold text-red-700">
                            <span>📞</span> Emergency Contacts
                        </div>
                    </div>

                    <div class="px-5 py-4 space-y-3">
                        @forelse($contacts as $c)
                            @php $isPrimary = ((int) $c->priority) === 1; @endphp
                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <div class="font-semibold text-gray-900">{{ explode('-', $c->name)[0] ?? '-' }}</div>
                                        @if($isPrimary)
                                            <span class="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-semibold text-white">Primary</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-600">{{ $c->relationship ?? '' }}</div>
                                </div>

                                @if($c->phone)
                                    <a href="tel:{{ $c->phone }}"
                                    class="inline-flex items-center text-nowrap gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                        📞 Call Now
                                    </a>
                                @endif
                            </div>
                        @empty
                            <div class="text-sm text-gray-600">No contacts available.</div>
                        @endforelse
                    </div>
                </div>

                {{-- MEDICAL INFORMATION --}}
                <div class="rounded-2xl border border-red-200 bg-white shadow-sm">
                    <div class="rounded-t-2xl border-b border-red-100 bg-red-50 px-5 py-3">
                        <div class="flex items-center gap-2 text-sm font-semibold text-red-700">
                            <span>❤️</span> Medical Information
                        </div>
                    </div>

                    @if ($currentPlan !== "registered" && $currentPlan !== "basic")
                    <div class="px-5 py-5 space-y-5">

                        {{-- Allergies --}}
                        <div>
                            <div class="flex items-center gap-2 font-semibold text-gray-900">
                                <span class="text-red-600">⛔</span> ALLERGIES
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @php $all = is_array($profile->allergies) ? $profile->allergies : []; @endphp
                                @forelse($all as $a)
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">{{ $a }}</span>
                                @empty
                                    <span class="text-sm text-gray-600">None listed</span>
                                @endforelse
                            </div>
                        </div>

                        {{-- Medications --}}
                        <div>
                            <div class="font-semibold text-gray-900">Current Medications</div>
                            @php $meds = is_array($profile->current_medications) ? $profile->current_medications : []; @endphp
                            <div class="mt-2 space-y-2">
                                @forelse($meds as $m)
                                    <div class="rounded-lg bg-blue-50 px-3 py-2 text-sm text-gray-800">{{ $m }}</div>
                                @empty
                                    <div class="text-sm text-gray-600">None listed</div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Conditions --}}
                        <div>
                            <div class="font-semibold text-gray-900">Medical Conditions</div>
                            @php $conds = is_array($profile->medical_conditions) ? $profile->medical_conditions : []; @endphp
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse($conds as $cnd)
                                    <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">{{ $cnd }}</span>
                                @empty
                                    <span class="text-sm text-gray-600">None listed</span>
                                @endforelse
                            </div>
                        </div>

                        {{-- Insurance + Physician --}}
                        <div class="text-sm">
                            <div class="font-semibold text-gray-900">Insurance</div>

                            <div class="mt-2 space-y-1 text-gray-700">
                                <div>Provider: <span class="font-semibold">{{ $profile->insurance_provider ?? '—' }}</span></div>
                                <!-- <div>Policy #: <span class="font-semibold">{{ $profile->insurance_number ?? '—' }}</span></div> -->
                                <!-- <div>Primary Physician: <span class="font-semibold">{{ $profile->primary_physician_name ?? '—' }}</span></div>
                                <div>Physician Phone: <span class="font-semibold">{{ $profile->primary_physician_phone ?? '—' }}</span></div> -->
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">
                            <div class="font-semibold text-gray-900">Additional Medical Notes</div>
                            <div class="mt-1 text-sm text-gray-800">
                                {{ $profile->additional_medical_notes ?? '—' }}
                            </div>
                        </div>

                    </div>
                    @else
                    <div class="px-5 py-5 space-y-5">
                        <h1>🔒 Full medical profile is not available</h1>
                    </div>
                    @endif
                </div>

                <div class="pt-2 text-center text-xs text-gray-500">
                    <div>Last updated: {{ $profile->updated_at?->format('F j, Y') ?? '—' }}</div>
                    <div class="mt-1">Powered by Emerion Emergency Profile System</div>
                </div>

                
                <div class="rounded-2xl border border-red-200 bg-white shadow-sm">
                    <div class="rounded-2xl border bg-white p-6 shadow-sm">

                        @if ($locationSaved)
                            <p class="mt-4 text-sm text-green-600">Scan location saved.</p>
                        @else
                            <p class="mt-4 text-sm text-gray-600">Please make sure GPS is turned on. Getting your location...</p>
                        @endif

                        @if ($scanError)
                            <p class="mt-4 text-sm text-gray-600">{{ $scanError }}</p>                    
                        @endif
                    </div>
                </div>

            </div>

            @script
            <script>
                const saveLocation = () => {
                    if (!navigator.geolocation) {
                        $wire.set('location_text', 'Geolocation not supported');
                        $wire.saveScanLocation('qr_scan');
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            $wire.set('lat', Number(position.coords.latitude));
                            $wire.set('lng', Number(position.coords.longitude));
                            $wire.set('location_text', 'GPS captured');
                            $wire.saveScanLocation('qr_scan');
                        },
                        (error) => {
                            let message = 'Location permission denied';

                            if (error.code === 2) message = 'Location unavailable';
                            if (error.code === 3) message = 'Location timeout';

                            $wire.set('location_text', message);
                            $wire.saveScanLocation('qr_scan');
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                };

                saveLocation();
            </script>
            @endscript
        @else
            <div class="max-w-3xl mx-auto px-4 py-8 space-y-5">
            @auth
                @if (!$userHasProfile)
                <div class="max-w-3xl mx-auto px-4 py-8 space-y-5">
                    <div class="mt-6">
                        <button wire:click="claim" class="px-4 py-2 rounded bg-black text-white">
                            Register this Emerion kit to my account
                        </button>
                    </div>
                </div>
                @else
                <div class="min-h-screen flex items-center justify-center px-4">
                    <div class="w-full max-w-md rounded-2xl border bg-white p-6 shadow-sm text-center">
                        <div class="mx-auto mb-3 h-12 w-12 rounded-full bg-red-100 flex items-center justify-center text-red-700">!</div>
                        <h1 class="text-lg font-semibold text-gray-900">{{ $statusTitle }}</h1>
                        <p class="mt-1 text-sm text-gray-600">{{ $statusMessage }}</p>
                    </div>
                </div>
                @endif
            @else
                <div class="max-w-3xl mx-auto px-4 py-8 space-y-5">
                    <h1 class="text-lg font-semibold text-gray-900">{{ $statusTitle }}</h1>
                    <p class="text-sm text-gray-600">{{ $statusMessage }}</p>
                    <div class="mt-6 space-y-3">
                        @if ($asset->claim_code)
                            <div>
                                <label class="block text-sm">Claim Code</label>
                                <input class="w-full border rounded p-2" type="text" wire:model="claim_code">
                                @error('claim_code') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        @if(!$emailVerified)
                        <div class="space-y-4" wire:poll.1s="refreshCooldown">
                        @endif
                            @if ($emailVerified)         
                            <div>
                                {{ $email }}
                            </div>               
                            <div>
                                <label class="block text-sm">Name</label>
                                <input class="w-full border rounded p-2" type="text" wire:model="name">
                                @error('name') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="block text-sm">Password</label>
                                <input class="w-full border rounded p-2" type="password" wire:model="password">
                                @error('password') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                            </div>

                            <button class="px-4 py-2 rounded bg-red-600 text-white" wire:click="claim" wire:loading.attr="disabled" wire:confirm="Are you sure you want to submit this form?" type="submit">
                                Create account + Register Kit
                            </button>
                            
                            <div class="text-sm text-gray-600">
                                Already have an account? <a class="underline" href="/login">Login</a>
                            </div>
                            @else

                            @if (!$toggleOtpAndVerificaion)
                            <div>
                                <label class="block text-sm">Email</label>
                                <input class="w-full border rounded p-2" type="email" wire:model.live="email" wire:change="syncOtpCooldown">
                                @error('email') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                            </div>
                            @else
                            <div>
                                <label for="otp" class="block text-sm font-medium">OTP</label>
                                <input
                                    id="otp"
                                    type="text"
                                    wire:model.live="otp"
                                    maxlength="6"
                                    class="w-full rounded-lg border px-3 py-2"
                                    placeholder="Enter 6-digit OTP"
                                >
                                @error('otp')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button
                                type="button"
                                wire:click="verifyOtp"
                                wire:loading.attr="disabled"
                                class="rounded-lg border px-4 py-2"
                            >
                                Verify OTP
                            </button>
                            @endif
                            
                            <button
                                type="button"
                                wire:click="sendOtp"
                                wire:loading.attr="disabled"
                                @disabled($otpCooldown > 0 || blank($email) || $errors->has('email'))
                                class="rounded-lg bg-red-600 px-4 py-2 text-white disabled:opacity-50"
                            >
                                @if ($otpCooldown > 0)
                                    Resend in {{ $otpCooldown }}s
                                @else
                                    Send OTP
                                @endif
                            </button>

                            @endif

                        @if(!$emailVerified)
                        </div>
                        @endif
                    </div>
                </div>
            @endauth
            </div>
        @endif
    </x-layouts.marketing>
</div>
@endvolt