<?php

use function Laravel\Folio\{middleware, name};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\EmergencyProfile;

name('health-information');

new class extends Component
{
    public ?EmergencyProfile $profile = null;

    // Lists
    public array $allergies = [];
    public array $current_medications = [];   // store as array of strings (MVP)
    public array $medical_conditions = [];

    // Input boxes
    public string $allergyInput = '';
    public string $medicationInput = '';
    public string $conditionInput = '';

    // Insurance & physician
    public ?string $insurance_provider = null;
    public ?string $insurance_number = null;
    public ?string $primary_physician_name = null;
    public ?string $primary_physician_phone = null;

    // Notes
    public ?string $additional_medical_notes = null;

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

		// if ($user->emerionAccessLocked() && request()->route() && !request()->routeIs(...$allowed)) {
		// 	redirect()->route('settings.subscription');
		// 	return;
		// }

        if (
            !$user->safeid_hide_onboarding &&
            is_null($user->safeid_setup_completed_at)
        ) {
            redirect()->to('emergency-profile-setup');
            return;
        }

        $this->profile = $user->emergencyProfile()->first();

        if (!$this->profile) {
            $this->profile = $user->emergencyProfile()->create([
                'uuid' => (string) Str::uuid(),
                'is_public' => true,
                'is_active' => true,
                'country' => 'PH',
            ]);
        }

        $this->fillFromProfile();
    }

    protected function fillFromProfile(): void
    {
        $p = $this->profile;

        $this->allergies = is_array($p->allergies) ? $p->allergies : [];
        $this->current_medications = is_array($p->current_medications) ? $p->current_medications : [];
        $this->medical_conditions = is_array($p->medical_conditions) ? $p->medical_conditions : [];

        $this->insurance_provider = $p->insurance_provider;
        $this->insurance_number = $p->insurance_number;
        $this->primary_physician_name = $p->primary_physician_name;
        $this->primary_physician_phone = $p->primary_physician_phone;
        $this->additional_medical_notes = $p->additional_medical_notes;
    }

    public function rules(): array
    {
        return [
            'allergies' => ['array', 'max:50'],
            'allergies.*' => ['string', 'max:60'],

            'current_medications' => ['array', 'max:50'],
            'current_medications.*' => ['string', 'max:120'],

            'medical_conditions' => ['array', 'max:50'],
            'medical_conditions.*' => ['string', 'max:60'],

            'insurance_provider' => ['nullable', 'string', 'max:120'],
            'insurance_number' => ['nullable', 'string', 'max:60'],
            'primary_physician_name' => ['nullable', 'string', 'max:120'],
            'primary_physician_phone' => ['nullable', 'string', 'max:40'],

            'additional_medical_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    // ---------- Add/Remove items ----------
    public function addAllergy(): void
    {
        $value = trim($this->allergyInput);
        if ($value === '') return;

        $value = Str::of($value)->squish()->toString();

        if (!in_array($value, $this->allergies, true)) {
            $this->allergies[] = $value;
        }

        $this->allergyInput = '';
    }

    public function removeAllergy(int $index): void
    {
        if (!isset($this->allergies[$index])) return;
        unset($this->allergies[$index]);
        $this->allergies = array_values($this->allergies);
    }

    public function addMedication(): void
    {
        $value = trim($this->medicationInput);
        if ($value === '') return;

        $value = Str::of($value)->squish()->toString();

        // allow duplicates? usually no
        if (!in_array($value, $this->current_medications, true)) {
            $this->current_medications[] = $value;
        }

        $this->medicationInput = '';
    }

    public function removeMedication(int $index): void
    {
        if (!isset($this->current_medications[$index])) return;
        unset($this->current_medications[$index]);
        $this->current_medications = array_values($this->current_medications);
    }

    public function addCondition(): void
    {
        $value = trim($this->conditionInput);
        if ($value === '') return;

        $value = Str::of($value)->squish()->toString();

        if (!in_array($value, $this->medical_conditions, true)) {
            $this->medical_conditions[] = $value;
        }

        $this->conditionInput = '';
    }

    public function removeCondition(int $index): void
    {
        if (!isset($this->medical_conditions[$index])) return;
        unset($this->medical_conditions[$index]);
        $this->medical_conditions = array_values($this->medical_conditions);
    }

    // ---------- Save/Cancel ----------
    public function save(): void
    {
        $this->validate();

        $this->profile->update([
            'allergies' => $this->allergies,
            'current_medications' => $this->current_medications,
            'medical_conditions' => $this->medical_conditions,

            'insurance_provider' => $this->insurance_provider ?: null,
            'insurance_number' => $this->insurance_number ?: null,
            'primary_physician_name' => $this->primary_physician_name ?: null,
            'primary_physician_phone' => $this->primary_physician_phone ?: null,

            'additional_medical_notes' => $this->additional_medical_notes ?: null,
        ]);

        session()->flash('status', 'Health information saved.');
        $this->dispatch('toast', message: 'Health information saved.');
    }

    public function cancel(): void
    {
        $this->fillFromProfile();
        $this->allergyInput = '';
        $this->medicationInput = '';
        $this->conditionInput = '';
        $this->resetValidation();

        $this->dispatch('toast', message: 'Changes reverted.');
    }
};
?>
@volt('health-information')
<div>
    <x-layouts.app>
        <x-app.container x-data x-cloak>
            <div class="min-h-screen bg-gray-50">
                <div class="max-w-full mx-auto px-4 py-8">
                    <div class="mb-6">
                        <h1 class="text-2xl font-semibold text-gray-900">Health Information</h1>
                        <p class="text-sm text-gray-600">Manage your medical history and health details</p>
                    </div>

                    @if (session('status'))
                        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="space-y-5">

                        <!-- Allergies -->
                        <div class="rounded-xl border bg-white p-6 shadow-sm">
                            <div class="mb-3 flex items-start gap-3">
                                <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-700">
                                    <!-- alert icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.732-3l-7-12a2 2 0 00-3.464 0l-7 12A2 2 0 005 19z"/>
                                    </svg>
                                </span>
                                <div>
                                    <h2 class="font-semibold text-gray-900">Allergies</h2>
                                    <p class="text-sm text-gray-600">List all known allergies</p>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <input type="text"
                                    wire:model.defer="allergyInput"
                                    wire:keydown.enter.prevent="addAllergy"
                                    placeholder="Enter allergy (e.g., Peanuts, Latex)"
                                    class="w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500" />

                                <button type="button"
                                        wire:click="addAllergy"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-600 text-white hover:bg-red-700">
                                    +
                                </button>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($allergies as $i => $item)
                                    <span class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700">
                                        {{ $item }}
                                        <button type="button" wire:click="removeAllergy({{ $i }})" class="text-red-700/70 hover:text-red-800">
                                            ×
                                        </button>
                                    </span>
                                @endforeach
                            </div>

                            @error('allergies') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Current Medications -->
                        <div class="rounded-xl border bg-white p-6 shadow-sm">
                            <div class="mb-3 flex items-start gap-3">
                                <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                                    <!-- pill icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 14l-2 2a4 4 0 105.657-5.657l-2 2m-1.414 1.414L14 10m-4 4l4-4"/>
                                    </svg>
                                </span>
                                <div>
                                    <h2 class="font-semibold text-gray-900">Current Medications</h2>
                                    <p class="text-sm text-gray-600">List all medications you're currently taking</p>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <input type="text"
                                    wire:model.defer="medicationInput"
                                    wire:keydown.enter.prevent="addMedication"
                                    placeholder="Enter medication and dosage (e.g., Aspirin 100mg - Daily)"
                                    class="w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500" />

                                <button type="button"
                                        wire:click="addMedication"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-600 text-white hover:bg-red-700">
                                    +
                                </button>
                            </div>

                            <div class="mt-3 space-y-2">
                                @foreach($current_medications as $i => $med)
                                    <div class="flex items-center justify-between gap-3 rounded-lg bg-blue-50 px-3 py-2">
                                        <div class="text-sm text-gray-800">{{ $med }}</div>
                                        <button type="button"
                                                wire:click="removeMedication({{ $i }})"
                                                class="rounded-md p-2 text-red-600 hover:bg-white/60">
                                            <!-- trash -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 0a1 1 0 00-1 1v1h6V4a1 1 0 00-1-1m-4 0h4"/>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Medical Conditions -->
                        <div class="rounded-xl border bg-white p-6 shadow-sm">
                            <div class="mb-3 flex items-start gap-3">
                                <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-700">
                                    <!-- heart icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                </span>
                                <div>
                                    <h2 class="font-semibold text-gray-900">Medical Conditions</h2>
                                    <p class="text-sm text-gray-600">List any ongoing medical conditions</p>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <input type="text"
                                    wire:model.defer="conditionInput"
                                    wire:keydown.enter.prevent="addCondition"
                                    placeholder="Enter medical condition"
                                    class="w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500" />

                                <button type="button"
                                        wire:click="addCondition"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-600 text-white hover:bg-red-700">
                                    +
                                </button>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($medical_conditions as $i => $item)
                                    <span class="inline-flex items-center gap-2 rounded-full bg-purple-50 px-3 py-1 text-xs font-medium text-purple-700">
                                        {{ $item }}
                                        <button type="button" wire:click="removeCondition({{ $i }})" class="text-purple-700/70 hover:text-purple-800">
                                            ×
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <!-- Insurance & Primary Physician -->
                        <div class="rounded-xl border bg-white p-6 shadow-sm">
                            <div class="mb-4">
                                <h2 class="font-semibold text-gray-900">Insurance &amp; Primary Physician</h2>
                                <p class="text-sm text-gray-600">Your insurance and doctor information</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Insurance Provider</label>
                                    <input type="text" wire:model.defer="insurance_provider"
                                        class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"
                                        placeholder="e.g., Maxicare / PhilHealth" />
                                    @error('insurance_provider') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Insurance Number</label>
                                    <input type="text" wire:model.defer="insurance_number"
                                        class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"
                                        placeholder="e.g., BC123456789" />
                                    @error('insurance_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Primary Physician</label>
                                    <input type="text" wire:model.defer="primary_physician_name"
                                        class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"
                                        placeholder="e.g., Dr. Sarah Johnson" />
                                    @error('primary_physician_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Physician Phone</label>
                                    <input type="text" wire:model.defer="primary_physician_phone"
                                        class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"
                                        placeholder="+63 ..." />
                                    @error('primary_physician_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Additional Medical Notes -->
                        <div class="rounded-xl border bg-white p-6 shadow-sm">
                            <div class="mb-4">
                                <h2 class="font-semibold text-gray-900">Additional Medical Notes</h2>
                                <p class="text-sm text-gray-600">Any other relevant medical information</p>
                            </div>

                            <textarea wire:model.defer="additional_medical_notes" rows="4"
                                    class="w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"
                                    placeholder="History of minor heart condition. Regular checkups required."></textarea>
                            @error('additional_medical_notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-1">
                            <button type="button"
                                    wire:click="cancel"
                                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>

                            <button type="button"
                                    wire:click="save"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60">
                                <svg wire:loading class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </div>
                </div>

                <script>
                    window.addEventListener('toast', (e) => console.log(e.detail?.message || 'Saved'));
                </script>
            </div>
        </x-app.container>
    </x-layouts.app>
</div>
@endvolt