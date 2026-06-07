<?php

use function Laravel\Folio\{middleware, name};
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\EmergencyProfile;
use App\Models\EmergencyContact;

name('emergency-contact');

new class extends Component
{
    public ?EmergencyProfile $profile = null;

    public $contacts;
    public $user;

    public bool $showModal = false;
    public ?int $editingId = null;

    // Modal fields
    public string $firstname = '';
    public string $lastname = '';
    public string $relationship = '';
    public string $phone = '';
    public string $email = '';

    public function mount(): void
    {
        $user = Auth::user();

        if (!$user) {
			redirect()->to('/');
			return;
		}

        $this->user = $user;

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
        
        // Ensure profile exists
        $this->profile = $user->emergencyProfile()->first();
        if (!$this->profile) {
            $this->profile = $user->emergencyProfile()->create([
                'first_name' => $user->name ?? null,
                'is_public' => true,
                'is_active' => true,
                'country' => 'PH',
            ]);
        }

        $this->refreshContacts();
    }

    protected function refreshContacts(): void
    {
        $this->contacts = EmergencyContact::query()
            ->where('profile_id', $this->profile->id)
            ->orderBy('priority') // 1 = primary
            ->orderBy('id', 'desc')
            ->get();
    }

    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:120'],
            'lastname' => ['required', 'string', 'max:120'],
            'relationship' => ['nullable', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetModalFields();
        $this->editingId = null;
        $this->showModal = true;
        $this->resetValidation();
    }

    public function openEdit(int $contactId): void
    {
        $contact = EmergencyContact::where('profile_id', $this->profile->id)
            ->findOrFail($contactId);
        $fullname = explode('-', $contact->name);
        $fname = $fullname[0] ?? '';
        $lname = $fullname[1] ?? '';
        $this->editingId = $contact->id;
        $this->firstname = $fname;
        $this->lastname = $lname;
        $this->relationship = $contact->relationship ?? '';
        $this->phone = $contact->phone ?? '';
        $this->email = $contact->email ?? '';

        $this->showModal = true;
        $this->resetValidation();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    protected function resetModalFields(): void
    {
        $this->name = '';
        $this->relationship = '';
        $this->phone = '';
        $this->email = '';
    }

    public function saveContact(): void
    {
        $this->validate();

        if ($this->user->hasRole(['registered', 'trial', 'basic', '']) && EmergencyContact::where('profile_id', $this->profile->id)->count() == 1) {
            session()->flash('error', 'Please upgrade to add more emergency contact info.');
        } else {

            DB::transaction(function () {
                $data = [
                    'profile_id' => $this->profile->id,
                    'name' => $this->firstname . '-' . $this->lastname,
                    'relationship' => $this->relationship ?: null,
                    'phone' => $this->phone ?: null,
                    'email' => $this->email ?: null,
                ];

                if ($this->editingId) {
                    EmergencyContact::where('profile_id', $this->profile->id)
                        ->where('id', $this->editingId)
                        ->update($data);
                } else {
                    // If first contact -> make primary by default
                    $isFirst = EmergencyContact::where('profile_id', $this->profile->id)->count() === 0;
                    $data['priority'] = $isFirst ? 1 : 2;

                    EmergencyContact::create($data);

                    // If not first, keep existing primary as priority 1
                }
            });

            $this->closeModal();
            $this->refreshContacts();
            session()->flash('status', 'Contact saved.');
        }
    }

    public function deleteContact(int $contactId): void
    {
        DB::transaction(function () use ($contactId) {
            $contact = EmergencyContact::where('profile_id', $this->profile->id)
                ->findOrFail($contactId);

            $wasPrimary = ((int) $contact->priority) === 1;

            $contact->delete();

            // If deleted primary, promote the next one to primary
            if ($wasPrimary) {
                $next = EmergencyContact::where('profile_id', $this->profile->id)
                    ->orderBy('id')
                    ->first();

                if ($next) {
                    $next->update(['priority' => 1]);
                }
            }
        });

        $this->refreshContacts();
        session()->flash('status', 'Contact deleted.');
    }

    public function setPrimary(int $contactId): void
    {
        DB::transaction(function () use ($contactId) {
            // Set all to non-primary (priority 2)
            EmergencyContact::where('profile_id', $this->profile->id)->update(['priority' => 2]);

            // Set selected to primary (priority 1)
            EmergencyContact::where('profile_id', $this->profile->id)
                ->where('id', $contactId)
                ->update(['priority' => 1]);
        });

        $this->refreshContacts();
        session()->flash('status', 'Primary contact updated.');
    }
};
?>

@volt('emergency-contact')
<div>
    <x-layouts.app>
        <x-app.container x-data x-cloak>
            <div class="min-h-screen bg-gray-50">
                <div class="max-w-full mx-auto px-4 py-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">Emergency Contacts</h1>
                            <p class="text-sm text-gray-600">Manage your emergency contact list (minimum 1 required)</p>
                        </div>

                        <button type="button"
                                wire:click="openCreate"
                                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                            <span class="text-lg leading-none">+</span>
                            <span>Add Contact</span>
                        </button>
                    </div>

                    @if (session('status'))
                        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="mt-6 space-y-4">
                        @forelse($contacts as $contact)
                            @php $isPrimary = ((int) $contact->priority) === 1; @endphp

                            <div class="rounded-xl border bg-white p-5 shadow-sm
                                        {{ $isPrimary ? 'border-red-300 ring-1 ring-red-200' : 'border-gray-200' }}">
                                <div class="flex items-start justify-between gap-4">
                                    <button type="button" wire:click="openEdit({{ $contact->id }})"
                                            class="flex w-full items-start gap-4 text-left">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-700">
                                            <!-- person icon -->
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
                                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                                                        Primary
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="mt-3 space-y-2 text-sm text-gray-700">
                                                @if($contact->phone)
                                                    <div class="flex items-center gap-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.128a11.042 11.042 0 005.516 5.516l1.128-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 16.72V20a2 2 0 01-2 2h-1C9.716 22 3 15.284 3 7V5z"/>
                                                        </svg>
                                                        <span>{{ $contact->phone }}</span>
                                                    </div>
                                                @endif

                                                @if($contact->email)
                                                    <div class="flex items-center gap-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-18 8h18a2 2 0 002-2V8a2 2 0 00-2-2H3a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                                        </svg>
                                                        <span>{{ $contact->email }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </button>

                                    <button type="button"
                                            wire:click="deleteContact({{ $contact->id }})"
                                            wire:confirm="Delete this contact?"
                                            class="rounded-lg p-2 text-red-600 hover:bg-red-50">
                                        <!-- trash icon -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 0a1 1 0 00-1 1v1h6V4a1 1 0 00-1-1m-4 0h4"/>
                                        </svg>
                                    </button>
                                </div>

                                @if(!$isPrimary)
                                    <div class="mt-4">
                                        <button type="button"
                                                wire:click="setPrimary({{ $contact->id }})"
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
                        
                    
                        <a href="/health-information" class="bg-gray-800 hover:bg-gray-700 transition px-5 py-2 rounded-xl font-semibold text-white float-right">
                            Next
                        </a>
                    </div>
                </div>

                <!-- Modal -->
                @if($showModal)
                    <div class="fixed inset-0 z-50 flex items-center justify-center">
                        <!-- backdrop -->
                        <div class="absolute inset-0 bg-black/50" wire:click="closeModal"></div>

                        <!-- modal panel -->
                        <div class="relative z-10 w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">
                                        {{ $editingId ? 'Edit Emergency Contact' : 'Add Emergency Contact' }}
                                    </h2>
                                    <p class="text-sm text-gray-600">Add someone who can be reached in case of emergency</p>
                                    
                                
                                    @if (session('error'))
                                        <div class="mt-4 w-full rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                                            {{ session('error') }} <br>
                                            <a href="{{ route('settings.subscription') }}"
                                            class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                                Upgrade to continue
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <button type="button" wire:click="closeModal" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-5 space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Full Name</label>
                                    <input type="text" wire:model.defer="firstname"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500"
                                        placeholder="First name" />
                                    @error('firstname') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    <input type="text" wire:model.defer="lastname"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500"
                                        placeholder="Last name" />
                                    @error('lastname') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Relationship</label>
                                    <!-- <input type="text" wire:model.defer="relationship"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500"
                                        placeholder="e.g., Spouse, Parent, Friend" /> -->
                                    <select wire:model.defer="relationship"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500">
                                        
                                        <option value="">Select relationship</option>
                                        <option value="Spouse">Spouse</option>
                                        <option value="Parent">Parent</option>
                                        <option value="Child">Child</option>
                                        <option value="Sibling">Sibling</option>
                                        <option value="Friend">Friend</option>
                                        <option value="Guardian">Guardian</option>
                                        <option value="Colleague">Colleague</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    @error('relationship') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Phone Number</label>
                                    <input type="text" wire:model.defer="phone"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500"
                                        placeholder="+63 9XX XXX XXXX" />
                                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" wire:model.defer="email"
                                        class="mt-1 w-full rounded-lg border-gray-200 focus:border-red-500 focus:ring-red-500"
                                        placeholder="contact@example.com" />
                                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <button type="button"
                                        wire:click="saveContact"
                                        wire:loading.attr="disabled"
                                        class="mt-2 w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60">
                                    {{ $editingId ? 'Save Changes' : 'Add Contact' }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-app.container>
    </x-layouts.app>
</div>
@endvolt
