<?php

use function Laravel\Folio\{middleware, name};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\EmergencyProfile;

name('emergency-profile');

new class extends Component
{
    public ?EmergencyProfile $profile = null;

    // Form fields
    public ?string $first_name = null;
    public ?string $last_name = null;
    public ?string $birthdate = null;     // YYYY-MM-DD
    public ?string $blood_type = null;

    public ?int $height_cm = null;
    public ?int $weight_kg = null;

    public ?string $address_line = null;
    public ?string $city = null;
    public ?string $province = null;      // state/province
    public ?string $zip_code = null;

    public ?string $additional_medical_notes = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (!$user) {
			redirect()->to('/');
			return;
		}

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

        // Assumes User has: emergencyProfile() relationship
        // public function emergencyProfile(){ return $this->hasOne(EmergencyProfile::class); }
        $this->profile = $user->emergencyProfile()->first();

        if ($this->profile) {
            $this->fill([
                'first_name' => $this->profile->first_name,
                'last_name' => $this->profile->last_name,
                'birthdate' => optional($this->profile->birthdate)->format('Y-m-d'),
                'blood_type' => $this->profile->blood_type,

                'height_cm' => $this->profile->height_cm,
                'weight_kg' => $this->profile->weight_kg,

                'address_line' => $this->profile->address_line,
                'city' => $this->profile->city,
                'province' => $this->profile->province,
                'zip_code' => $this->profile->zip_code,

                'additional_medical_notes' => $this->profile->additional_medical_notes,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name'  => ['nullable', 'string', 'max:80'],
            'birthdate'  => ['nullable', 'date', 'before:today'],
            'blood_type' => ['nullable', 'string', Rule::in(['A+','A-','B+','B-','AB+','AB-','O+','O-'])],

            'height_cm'  => ['nullable', 'integer', 'min:30', 'max:300'],
            'weight_kg'  => ['nullable', 'integer', 'min:1', 'max:500'],

            'address_line' => ['nullable', 'string', 'max:255'],
            'city'         => ['nullable', 'string', 'max:120'],
            'province'     => ['nullable', 'string', 'max:120'],
            'zip_code'     => ['nullable', 'string', 'max:20'],

            'additional_medical_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $user = Auth::user();

        $payload = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'birthdate' => $this->birthdate ?: null,
            'blood_type' => $this->blood_type ?: null,

            'height_cm' => $this->height_cm,
            'weight_kg' => $this->weight_kg,

            'address_line' => $this->address_line,
            'city' => $this->city,
            'province' => $this->province,
            'zip_code' => $this->zip_code,

            'additional_medical_notes' => $this->additional_medical_notes,

            // keep these sane defaults for MVP
            'is_public' => true,
            'is_active' => true,
            'country' => $this->profile?->country ?? 'PH',
        ];

        // Create if missing, else update
        $this->profile = $user->emergencyProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $payload
        );

        $this->dispatch('toast', message: 'Emergency profile saved successfully.');
        session()->flash('status', 'Emergency profile saved successfully.');
    }

    public function cancel(): void
    {
        // Just reload from DB
        $this->mount();
        $this->resetValidation();
        $this->dispatch('toast', message: 'Changes reverted.');
    }
};
?>

@volt('emergency-profile')
<div>
    <x-layouts.app>
        <x-app.container x-data x-cloak>
            <div class="min-h-screen bg-gray-50">
                <div class="max-w-full mx-auto px-4 py-8">
                    <div class="mb-6">
                        <h1 class="text-2xl font-semibold text-gray-900">Emergency Profile</h1>
                        <p class="text-sm text-gray-600">Update your personal information for emergency situations</p>
                    </div>

                    @if (session('status'))
                        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="space-y-6">
                        <!-- Personal Information -->
                        <div class="rounded-xl border bg-white p-6 shadow-sm">
                            <div class="mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-700">
                                        <!-- icon -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </span>
                                    <div>
                                        <h2 class="font-semibold text-gray-900">Personal Information</h2>
                                        <p class="text-sm text-gray-600">Basic details about yourself</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">First Name</label>
                                    <input type="text" wire:model.defer="first_name"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500" />
                                    @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Last Name</label>
                                    <input type="text" wire:model.defer="last_name"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500" />
                                    @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Date of Birth</label>
                                    <input type="date" wire:model.defer="birthdate"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500" />
                                    @error('birthdate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Blood Type</label>
                                    <select wire:model.defer="blood_type"
                                            class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500">
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
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500" />
                                    @error('height_cm') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Weight (kg)</label>
                                    <input type="number" wire:model.defer="weight_kg" min="1" max="500"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500" />
                                    @error('weight_kg') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Address Information -->
                        <div class="rounded-xl border bg-white p-6 shadow-sm">
                            <div class="mb-4">
                                <h2 class="font-semibold text-gray-900">Address Information</h2>
                                <p class="text-sm text-gray-600">Your current residence</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div class="md:col-span-3">
                                    <label class="text-sm font-medium text-gray-700">Street Address</label>
                                    <input type="text" wire:model.defer="address_line"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500" />
                                    @error('address_line') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">City</label>
                                    <input type="text" wire:model.defer="city"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500" />
                                    @error('city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">State / Province</label>
                                    <input type="text" wire:model.defer="province"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500" />
                                    @error('province') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">ZIP Code</label>
                                    <input type="text" wire:model.defer="zip_code"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500" />
                                    @error('zip_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="rounded-xl border bg-white p-6 shadow-sm">
                            <div class="mb-4">
                                <h2 class="font-semibold text-gray-900">Additional Notes</h2>
                                <p class="text-sm text-gray-600">Any other important information</p>
                            </div>

                            <textarea wire:model.defer="additional_medical_notes" rows="4"
                                    class="w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500"
                                    placeholder="e.g. No known allergies, special instructions, etc."></textarea>
                            @error('additional_medical_notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3">
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
                            <a href="/emergency-contact"
                            class="bg-gray-800 hover:bg-gray-700 transition px-5 py-2 rounded-xl font-semibold text-white">
                                Next
                            </a>
                        </div>
                    </div>
                </div>

                <script>
                    // Optional toast hook (works if you have your own toast listener)
                    window.addEventListener('toast', (e) => {
                        // You can connect this to your UI toast component
                        console.log(e.detail?.message || 'Saved');
                    });
                </script>
            </div>
        </x-app.container>
    </x-layouts.app>
</div>
@endvolt