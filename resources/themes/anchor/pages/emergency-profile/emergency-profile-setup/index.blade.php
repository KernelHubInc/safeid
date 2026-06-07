<?php

use function Laravel\Folio\{middleware, name};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use App\Models\EmergencyProfile;
use App\Models\EmergencyContact;

name('emergency-profile-setup');
new class extends Component
{
    // UI state
    public int $step = 0; // 0 = instructions, 1 = profile, 2 = health, 3 = contacts
    public bool $dontShowAgain = false;

    public array $savedSteps = [1 => false, 2 => false, 3 => false];

    public ?EmergencyProfile $profile = null;

    // -------------------------
    // STEP 1: Emergency Profile
    // -------------------------
    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $birthdate = null;
    public ?string $blood_type = null;
    public ?int $height_cm = null;
    public ?int $weight_kg = null;

    public ?string $address_line = null;
    public ?string $city = null;
    public ?string $province = null;
    public ?string $zip_code = null;

    public ?string $profile_notes = null; // additional notes for Step 1

    // -------------------------
    // STEP 2: Health Info
    // -------------------------
    public array $allergies = [];
    public array $current_medications = []; // MVP strings
    public array $medical_conditions = [];

    public string $allergyInput = '';
    public string $medicationInput = '';
    public string $conditionInput = '';

    public ?string $insurance_provider = null;
    public ?string $insurance_number = null;
    public ?string $primary_physician_name = null;
    public ?string $primary_physician_phone = null;
    public ?string $additional_medical_notes = null;

    // -------------------------
    // STEP 3: Contacts
    // -------------------------
    public $contacts;
    public bool $showContactModal = false;
    public ?int $editingContactId = null;

    public string $c_name = '';
    public string $c_relationship = '';
    public string $c_phone = '';
    public string $c_email = '';

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

        if ($user->safeid_setup_completed_at) {
            redirect()->route('dashboard');
        }

        // If they opted out of instruction, start on step 1
        $this->step = $user->safeid_hide_onboarding ? 1 : 0;
        $this->dontShowAgain = (bool) $user->safeid_hide_onboarding;

        // Ensure profile exists
        $this->profile = $user->emergencyProfile()->first();
        if (!$this->profile) {
            $this->profile = $user->emergencyProfile()->create([
                'uuid' => (string) Str::uuid(),
                'is_public' => true,
                'is_active' => true,
                'country' => 'PH',
            ]);
        }

        $this->fillStep1FromProfile();
        $this->fillStep2FromProfile();
        $this->refreshContacts();
    }

    // --------- Helpers ---------
    protected function fillStep1FromProfile(): void
    {
        $p = $this->profile;

        $this->first_name = $p->first_name;
        $this->last_name = $p->last_name;
        $this->birthdate = optional($p->birthdate)->format('Y-m-d');
        $this->blood_type = $p->blood_type;

        $this->height_cm = $p->height_cm;
        $this->weight_kg = $p->weight_kg;

        $this->address_line = $p->address_line;
        $this->city = $p->city;
        $this->province = $p->province;
        $this->zip_code = $p->zip_code;

        // We’ll store step1 notes in additional_medical_notes only if you want.
        // For now keep a separate field mapped to additional_medical_notes? (optional)
        $this->profile_notes = $p->profile_notes;
    }

    protected function fillStep2FromProfile(): void
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

    protected function refreshContacts(): void
    {
        $this->contacts = EmergencyContact::query()
            ->where('profile_id', $this->profile->id)
            ->orderBy('priority')
            ->orderBy('id', 'desc')
            ->get();
    }

    // --------- Instruction step ---------
    public function startWizard(): void
    {
        $user = Auth::user();

        $user->safeid_hide_onboarding = $this->dontShowAgain;
        $user->save();

        $this->step = 1;
    }

    // --------- Step navigation ---------
    public function goToStep(int $step): void
    {
        $this->step = max(0, min(3, $step));
        $this->resetValidation();
    }

    public function next(): void
    {
        if ($this->step === 1) $this->saveStep1();
        if ($this->step === 2) $this->saveStep2();

        if ($this->step < 3) $this->step++;
        $this->resetValidation();
    }

    public function back(): void
    {
        if ($this->step > 0) $this->step--;
        $this->resetValidation();
    }

    // --------- Step 1 validation/save ---------
    public function rulesStep1(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'blood_type' => ['nullable', 'string', Rule::in(['A+','A-','B+','B-','AB+','AB-','O+','O-'])],
            'height_cm' => ['nullable', 'integer', 'min:30', 'max:300'],
            'weight_kg' => ['nullable', 'integer', 'min:1', 'max:500'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'profile_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function saveStep1(): void
    {
        $this->validate($this->rulesStep1());

        $this->profile->update([
            'first_name' => $this->first_name ?: null,
            'last_name' => $this->last_name ?: null,
            'birthdate' => $this->birthdate ?: null,
            'blood_type' => $this->blood_type ?: null,
            'height_cm' => $this->height_cm,
            'weight_kg' => $this->weight_kg,
            'address_line' => $this->address_line ?: null,
            'city' => $this->city ?: null,
            'province' => $this->province ?: null,
            'zip_code' => $this->zip_code ?: null,
            'profile_notes' => $this->profile_notes ?: null,
        ]);

        $this->savedSteps[1] = true;
        $this->dispatch('step-saved', step: 1);
    }

    // --------- Step 2 add/remove items ---------
    public function addAllergy(): void
    {
        $value = Str::of($this->allergyInput)->squish()->trim()->toString();
        if ($value === '') return;

        if (!in_array($value, $this->allergies, true)) $this->allergies[] = $value;
        $this->allergyInput = '';
    }

    public function removeAllergy(int $i): void
    {
        if (!isset($this->allergies[$i])) return;
        unset($this->allergies[$i]);
        $this->allergies = array_values($this->allergies);
    }

    public function addMedication(): void
    {
        $value = Str::of($this->medicationInput)->squish()->trim()->toString();
        if ($value === '') return;

        if (!in_array($value, $this->current_medications, true)) $this->current_medications[] = $value;
        $this->medicationInput = '';
    }

    public function removeMedication(int $i): void
    {
        if (!isset($this->current_medications[$i])) return;
        unset($this->current_medications[$i]);
        $this->current_medications = array_values($this->current_medications);
    }

    public function addCondition(): void
    {
        $value = Str::of($this->conditionInput)->squish()->trim()->toString();
        if ($value === '') return;

        if (!in_array($value, $this->medical_conditions, true)) $this->medical_conditions[] = $value;
        $this->conditionInput = '';
    }

    public function removeCondition(int $i): void
    {
        if (!isset($this->medical_conditions[$i])) return;
        unset($this->medical_conditions[$i]);
        $this->medical_conditions = array_values($this->medical_conditions);
    }

    // --------- Step 2 validation/save ---------
    public function rulesStep2(): array
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

    public function saveStep2(): void
    {
        $this->validate($this->rulesStep2());

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

        $this->savedSteps[2] = true;
        $this->dispatch('step-saved', step: 2);
    }

    // --------- Step 3: Contacts modal ---------
    public function openCreateContact(): void
    {
        $this->resetContactModal();
        $this->editingContactId = null;
        $this->showContactModal = true;
        $this->resetValidation();
    }

    public function openEditContact(int $id): void
    {
        $c = EmergencyContact::where('profile_id', $this->profile->id)->findOrFail($id);

        $this->editingContactId = $c->id;
        $this->c_name = $c->name;
        $this->c_relationship = $c->relationship ?? '';
        $this->c_phone = $c->phone ?? '';
        $this->c_email = $c->email ?? '';

        $this->showContactModal = true;
        $this->resetValidation();
    }

    public function closeContactModal(): void
    {
        $this->showContactModal = false;
        $this->resetValidation();
    }

    protected function resetContactModal(): void
    {
        $this->c_name = '';
        $this->c_relationship = '';
        $this->c_phone = '';
        $this->c_email = '';
    }

    public function rulesContact(): array
    {
        return [
            'c_name' => ['required', 'string', 'max:120'],
            'c_relationship' => ['nullable', 'string', 'max:60'],
            'c_phone' => ['nullable', 'string', 'max:40'],
            'c_email' => ['nullable', 'email', 'max:120'],
        ];
    }

    public function saveContact(): void
    {
        $this->validate($this->rulesContact());

        DB::transaction(function () {
            $data = [
                'profile_id' => $this->profile->id,
                'name' => $this->c_name,
                'relationship' => $this->c_relationship ?: null,
                'phone' => $this->c_phone ?: null,
                'email' => $this->c_email ?: null,
            ];

            if ($this->editingContactId) {
                EmergencyContact::where('profile_id', $this->profile->id)
                    ->where('id', $this->editingContactId)
                    ->update($data);
            } else {
                $isFirst = EmergencyContact::where('profile_id', $this->profile->id)->count() === 0;
                $data['priority'] = $isFirst ? 1 : 2;
                EmergencyContact::create($data);
            }
        });

        $this->closeContactModal();
        $this->refreshContacts();
    }

    public function deleteContact(int $id): void
    {
        DB::transaction(function () use ($id) {
            $count = EmergencyContact::where('profile_id', $this->profile->id)->count();
            if ($count <= 1) {
                // keep at least 1
                session()->flash('status', 'You must have at least 1 emergency contact.');
                return;
            }

            $c = EmergencyContact::where('profile_id', $this->profile->id)->findOrFail($id);
            $wasPrimary = ((int) $c->priority) === 1;

            $c->delete();

            if ($wasPrimary) {
                $next = EmergencyContact::where('profile_id', $this->profile->id)->orderBy('id')->first();
                if ($next) $next->update(['priority' => 1]);
            }
        });

        $this->refreshContacts();
    }

    public function setPrimary(int $id): void
    {
        DB::transaction(function () use ($id) {
            EmergencyContact::where('profile_id', $this->profile->id)->update(['priority' => 2]);

            EmergencyContact::where('profile_id', $this->profile->id)
                ->where('id', $id)
                ->update(['priority' => 1]);
        });

        $this->refreshContacts();
    }

    public function finish()
    {
        // Save steps 1 & 2 to be safe
        $this->saveStep1();
        $this->saveStep2();

        $user = Auth::user();

        // Ensure at least 1 contact
        if ($user->hasRole('solo')) {
            if (EmergencyContact::where('profile_id', $this->profile->id)->count() < 1) {
                session()->flash('status', 'Please add at least 1 emergency contact to finish.');
                $this->step = 3;
                return;
            }
        }

        $user->safeid_setup_completed_at = now();
        $user->save();

        session()->flash('status', 'Setup complete! Your Emerion profile is ready.');
        $this->dispatch('toast', message: 'Setup complete!');
        $this->savedSteps[3] = true;
        $this->dispatch('step-saved', step: 3);

        // redirect wherever you want
        return redirect()->route('dashboard');
    }

    public function progressPercent(): int
    {
        // Step 1..3 only (instructions step 0 = 0%)
        return match ($this->step) {
            1 => 33,
            2 => 66,
            3 => 100,
            default => 0,
        };
    }

    public function progressLabel(): string
    {
        return match ($this->step) {
            1 => 'Step 1 of 3 • Emergency Profile',
            2 => 'Step 2 of 3 • Health Information',
            3 => 'Step 3 of 3 • Emergency Contacts',
            default => 'Getting started',
        };
    }

    public function isSetupCompleted(): bool
    {
        return !is_null(Auth::user()?->safeid_setup_completed_at);
    }

};
?>

@volt('emergency-profile-setup')
<div>
    <x-layouts.app>
        <x-app.container x-data x-cloak>
            <div class="min-h-screen bg-gray-50">
                <div class="max-w-full mx-auto px-4 py-8">

                    <!-- Top header -->
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">Emerion Setup</h1>
                            
                            @if($this->isSetupCompleted())
                                <span class="inline-flex items-center gap-2 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Completed
                                </span>
                            @else
                                <p class="text-sm text-gray-600">Complete these steps to make your emergency profile ready.</p>
                            @endif
                        </div>
                    </div>

                    @if (session('status'))
                        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    <!-- Stepper -->
                    <div class="mt-6 rounded-xl border bg-white p-4 shadow-sm">
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            @php
                                $steps = [
                                    1 => 'Emergency Profile',
                                    2 => 'Health Information',
                                    3 => 'Emergency Contacts',
                                ];
                            @endphp

                            @foreach($steps as $i => $label)
                                <button type="button"
                                        wire:click="goToStep({{ $i }})"
                                        class="flex items-center justify-center gap-2 rounded-lg px-3 py-2
                                            {{ $step === $i ? 'bg-red-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100' }}"
                                        x-data="{ showCheck: false }"
                                        x-on:step-saved.window="
                                            if ($event.detail.step == {{ $i }}) {
                                                showCheck = true;
                                                setTimeout(() => showCheck = false, 1200);
                                            }
                                        "
                                >
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full
                                                {{ $step === $i ? 'bg-white/20' : 'bg-white border border-gray-200' }}">
                                        {{ $i }}
                                    </span>

                                    <span class="font-medium">{{ $label }}</span>

                                    <!-- Persisted saved indicator (small) -->
                                    @if(($savedSteps[$i] ?? false) === true)
                                        <span class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-green-600 text-white">
                                            <!-- check icon -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                    @endif

                                    <!-- Animated pop check (temporary) -->
                                    <span x-show="showCheck"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0 scale-75"
                                        x-transition:enter-end="opacity-100 scale-110"
                                        x-transition:leave="transition ease-in duration-200"
                                        x-transition:leave-start="opacity-100 scale-110"
                                        x-transition:leave-end="opacity-0 scale-75"
                                        class="ml-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-green-600 text-white"
                                        style="display: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                </button>

                            @endforeach
                        </div>
                    </div>

                    @if($step >= 1 && $step <= 3)
                        <div class="mt-4 rounded-xl border bg-white p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-medium text-gray-700">
                                    {{ $this->progressLabel() }}
                                </div>
                                <div class="text-sm font-semibold text-red-700">
                                    {{ $this->progressPercent() }}%
                                </div>
                            </div>

                            <div class="mt-3 h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-2 rounded-full bg-red-600 transition-all duration-300"
                                    style="width: {{ $this->progressPercent() }}%"></div>
                            </div>

                            <div class="mt-2 flex justify-between text-xs text-gray-500">
                                <span>33%</span>
                                <span>66%</span>
                                <span>100%</span>
                            </div>
                        </div>
                    @endif


                    <!-- INSTRUCTIONS (Step 0) -->
                    @if($step === 0)
                        <div class="mt-6 rounded-xl border bg-white p-6 shadow-sm">
                            <h2 class="text-lg font-semibold text-gray-900">Before you start</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                You’ll complete 3 quick steps. This ensures your QR scan page shows the right emergency details.
                            </p>

                            <div class="mt-4 space-y-3 text-sm text-gray-700">
                                <div class="flex gap-3">
                                    <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-50 text-red-700">1</span>
                                    <div><span class="font-semibold">Emergency Profile</span> — basic identity + address</div>
                                </div>
                                <div class="flex gap-3">
                                    <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-50 text-red-700">2</span>
                                    <div><span class="font-semibold">Health Information</span> — allergies, medications, conditions</div>
                                </div>
                                <div class="flex gap-3">
                                    <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-50 text-red-700">3</span>
                                    <div><span class="font-semibold">Emergency Contacts</span> — at least 1 required for alerts</div>
                                </div>
                            </div>

                            <label class="mt-5 flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model="dontShowAgain" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                Don’t show this again
                            </label>

                            <div class="mt-6 flex justify-end">
                                <button type="button"
                                        wire:click="startWizard"
                                        class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                    Start Setup
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- STEP 1: Emergency Profile -->
                    @if($step === 1)
                        <div class="mt-6 space-y-6">
                            <div class="rounded-xl border bg-white p-6 shadow-sm">
                                <h2 class="font-semibold text-gray-900">Emergency Profile</h2>
                                <p class="text-sm text-gray-600">Basic details about yourself</p>

                                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">First Name</label>
                                        <input type="text" wire:model.defer="first_name"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"/>
                                        @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Last Name</label>
                                        <input type="text" wire:model.defer="last_name"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"/>
                                        @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Date of Birth</label>
                                        <input type="date" wire:model.defer="birthdate"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"/>
                                        @error('birthdate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Blood Type</label>
                                        <select wire:model.defer="blood_type"
                                                class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500">
                                            <option value="">Select</option>
                                            @foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bt)
                                                <option value="{{ $bt }}">{{ $bt }}</option>
                                            @endforeach
                                        </select>
                                        @error('blood_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Height (cm)</label>
                                        <input type="number" wire:model.defer="height_cm" min="30" max="300"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"/>
                                        @error('height_cm') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Weight (kg)</label>
                                        <input type="number" wire:model.defer="weight_kg" min="1" max="500"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"/>
                                        @error('weight_kg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border bg-white p-6 shadow-sm">
                                <h2 class="font-semibold text-gray-900">Address Information</h2>
                                <p class="text-sm text-gray-600">Your current residence</p>

                                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <div class="md:col-span-3">
                                        <label class="text-sm font-medium text-gray-700">Street Address</label>
                                        <input type="text" wire:model.defer="address_line"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"/>
                                        @error('address_line') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-700">City</label>
                                        <input type="text" wire:model.defer="city"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"/>
                                        @error('city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-700">State / Province</label>
                                        <input type="text" wire:model.defer="province"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"/>
                                        @error('province') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-gray-700">ZIP Code</label>
                                        <input type="text" wire:model.defer="zip_code"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"/>
                                        @error('zip_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border bg-white p-6 shadow-sm">
                                <h2 class="font-semibold text-gray-900">Additional Notes</h2>
                                <p class="text-sm text-gray-600">Any other important information</p>
                                <textarea wire:model.defer="profile_notes" rows="3"
                                        class="mt-3 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"
                                        placeholder="Optional notes..."></textarea>
                                @error('profile_notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif

                    <!-- STEP 2: Health Information (same UI as before, shortened for space) -->
                    @if($step === 2)
                        <div class="mt-6 space-y-5">
                            <!-- Allergies -->
                            <div class="rounded-xl border bg-white p-6 shadow-sm">
                                <div class="mb-3">
                                    <h2 class="font-semibold text-gray-900">Allergies</h2>
                                    <p class="text-sm text-gray-600">List all known allergies</p>
                                </div>

                                <div class="flex gap-2">
                                    <input type="text" wire:model.defer="allergyInput" wire:keydown.enter.prevent="addAllergy"
                                        placeholder="Enter allergy (e.g., Peanuts, Latex)"
                                        class="w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500" />
                                    <button type="button" wire:click="addAllergy"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-600 text-white hover:bg-red-700">+</button>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($allergies as $i => $item)
                                        <span class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700">
                                            {{ $item }}
                                            <button type="button" wire:click="removeAllergy({{ $i }})">×</button>
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Medications -->
                            <div class="rounded-xl border bg-white p-6 shadow-sm">
                                <div class="mb-3">
                                    <h2 class="font-semibold text-gray-900">Current Medications</h2>
                                    <p class="text-sm text-gray-600">List all medications you're currently taking</p>
                                </div>

                                <div class="flex gap-2">
                                    <input type="text" wire:model.defer="medicationInput" wire:keydown.enter.prevent="addMedication"
                                        placeholder="Enter medication and dosage (e.g., Aspirin 100mg - Daily)"
                                        class="w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500" />
                                    <button type="button" wire:click="addMedication"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-600 text-white hover:bg-red-700">+</button>
                                </div>

                                <div class="mt-3 space-y-2">
                                    @foreach($current_medications as $i => $med)
                                        <div class="flex items-center justify-between gap-3 rounded-lg bg-blue-50 px-3 py-2">
                                            <div class="text-sm text-gray-800">{{ $med }}</div>
                                            <button type="button" wire:click="removeMedication({{ $i }})"
                                                    class="rounded-md p-2 text-red-600 hover:bg-white/60">
                                                🗑
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Conditions -->
                            <div class="rounded-xl border bg-white p-6 shadow-sm">
                                <div class="mb-3">
                                    <h2 class="font-semibold text-gray-900">Medical Conditions</h2>
                                    <p class="text-sm text-gray-600">List any ongoing medical conditions</p>
                                </div>

                                <div class="flex gap-2">
                                    <input type="text" wire:model.defer="conditionInput" wire:keydown.enter.prevent="addCondition"
                                        placeholder="Enter medical condition"
                                        class="w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500" />
                                    <button type="button" wire:click="addCondition"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-600 text-white hover:bg-red-700">+</button>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($medical_conditions as $i => $item)
                                        <span class="inline-flex items-center gap-2 rounded-full bg-purple-50 px-3 py-1 text-xs font-medium text-purple-700">
                                            {{ $item }}
                                            <button type="button" wire:click="removeCondition({{ $i }})">×</button>
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Insurance + Notes -->
                            <div class="rounded-xl border bg-white p-6 shadow-sm">
                                <div class="mb-4">
                                    <h2 class="font-semibold text-gray-900">Insurance &amp; Primary Physician</h2>
                                    <p class="text-sm text-gray-600">Your insurance and doctor information</p>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Insurance Provider</label>
                                        <input type="text" wire:model.defer="insurance_provider"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500">
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Insurance Number</label>
                                        <input type="text" wire:model.defer="insurance_number"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500">
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Primary Physician</label>
                                        <input type="text" wire:model.defer="primary_physician_name"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500">
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-700">Physician Phone</label>
                                        <input type="text" wire:model.defer="primary_physician_phone"
                                            class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500">
                                    </div>
                                </div>

                                <div class="mt-5">
                                    <label class="text-sm font-medium text-gray-700">Additional Medical Notes</label>
                                    <textarea wire:model.defer="additional_medical_notes" rows="4"
                                            class="mt-2 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"></textarea>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- STEP 3: Contacts -->
                    @if($step === 3)
                        <div class="mt-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Emergency Contacts</h2>
                                    <p class="text-sm text-gray-600">Add at least 1 contact (required)</p>
                                </div>

                                <button type="button" wire:click="openCreateContact"
                                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                    <span class="text-lg leading-none">+</span>
                                    <span>Add Contact</span>
                                </button>
                            </div>

                            <div class="mt-5 space-y-4">
                                @forelse($contacts as $contact)
                                    @php $isPrimary = ((int) $contact->priority) === 1; @endphp
                                    <div class="rounded-xl border bg-white p-5 shadow-sm {{ $isPrimary ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }}">
                                        <div class="flex items-start justify-between gap-4">
                                            <button type="button" wire:click="openEditContact({{ $contact->id }})"
                                                    class="flex w-full items-start gap-4 text-left">
                                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                </div>

                                                <div class="flex-1">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <div class="font-semibold text-gray-900">{{ $contact->name }}</div>
                                                        @if($contact->relationship)
                                                            <div class="text-sm text-gray-600">{{ $contact->relationship }}</div>
                                                        @endif
                                                        @if($isPrimary)
                                                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Primary</span>
                                                        @endif
                                                    </div>

                                                    <div class="mt-3 space-y-2 text-sm text-gray-700">
                                                        @if($contact->phone) <div>📞 {{ $contact->phone }}</div> @endif
                                                        @if($contact->email) <div>✉️ {{ $contact->email }}</div> @endif
                                                    </div>
                                                </div>
                                            </button>

                                            <button type="button"
                                                    wire:click="deleteContact({{ $contact->id }})"
                                                    class="rounded-lg p-2 text-red-600 hover:bg-red-50">
                                                🗑
                                            </button>
                                        </div>

                                        @if(!$isPrimary)
                                            <div class="mt-4">
                                                <button type="button" wire:click="setPrimary({{ $contact->id }})"
                                                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                    Set as Primary
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600">
                                        No contacts yet. Click <span class="font-semibold text-red-700">Add Contact</span> to create one.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Contact Modal -->
                        @if($showContactModal)
                            <div class="fixed inset-0 z-50 flex items-center justify-center">
                                <div class="absolute inset-0 bg-black/50" wire:click="closeContactModal"></div>

                                <div class="relative z-10 w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h2 class="text-lg font-semibold text-gray-900">
                                                {{ $editingContactId ? 'Edit Emergency Contact' : 'Add Emergency Contact' }}
                                            </h2>
                                            <p class="text-sm text-gray-600">Add someone who can be reached in case of emergency</p>
                                        </div>

                                        <button type="button" wire:click="closeContactModal"
                                                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100">✕</button>
                                    </div>

                                    <div class="mt-5 space-y-4">
                                        <div>
                                            <label class="text-sm font-medium text-gray-700">Full Name</label>
                                            <input type="text" wire:model.defer="c_name"
                                                class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"
                                                placeholder="John Smith"/>
                                            @error('c_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-gray-700">Relationship</label>
                                            <input type="text" wire:model.defer="c_relationship"
                                                class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"
                                                placeholder="e.g., Spouse, Parent, Friend"/>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-gray-700">Phone Number</label>
                                            <input type="text" wire:model.defer="c_phone"
                                                class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"
                                                placeholder="+63 9XX XXX XXXX"/>
                                        </div>

                                        <div>
                                            <label class="text-sm font-medium text-gray-700">Email</label>
                                            <input type="email" wire:model.defer="c_email"
                                                class="mt-1 w-full rounded-lg border-gray-200 bg-gray-50 focus:border-red-500 focus:ring-red-500"
                                                placeholder="contact@example.com"/>
                                            @error('c_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>

                                        <button type="button" wire:click="saveContact"
                                                class="mt-2 w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                                            {{ $editingContactId ? 'Save Changes' : 'Add Contact' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Bottom nav buttons -->
                    <div class="mt-8 flex items-center justify-between">
                        <button type="button"
                                wire:click="back"
                                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                @if($step === 0) disabled @endif>
                            Back
                        </button>

                        <div class="flex items-center gap-3">
                            @if($step > 0 && $step < 3)
                                <button type="button"
                                        wire:click="next"
                                        class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                    Save & Next
                                </button>
                            @elseif($step === 3)
                                <button type="button"
                                        wire:click="finish"
                                        class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                    Finish Setup
                                </button>
                            @endif
                        </div>
                    </div>

                </div>

                <script>
                    window.addEventListener('toast', (e) => console.log(e.detail?.message || 'Done'));
                </script>
            </div>
        </x-app.container>
    </x-layouts.app>
</div>
@endvolt