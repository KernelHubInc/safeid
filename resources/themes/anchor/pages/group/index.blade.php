<?php

use function Laravel\Folio\{middleware, name};
use Livewire\Volt\Component;
use App\Models\EmerionActivityLog;
use App\Models\EmergencyProfile;
use App\Models\MemberLocation;
use App\Models\MemberStatusUpdate;
use App\Models\ScanLog;
use App\Models\Team;
use App\Models\TeamProvider;
use App\Models\User;
use App\Support\TeamActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

name('group');

new class extends Component
{
    public string $team_name = '';
    public string $email = '';

    public string $provider_type = 'doctor';
    public string $provider_name = '';
    public string $provider_phone = '';
    public string $provider_email = '';
    public string $provider_address = '';
    public string $provider_notes = '';

    public string $status = 'safe';
    public string $status_note = '';

    public ?Team $team = null;
    public bool $isCaptain = false;
    public $user = null;

    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user, 403);
        abort_unless($user->hasRole(['premium', 'enterprise']), 403);

        $ownedTeam = Team::query()
            ->with('owner')
            ->where('owner_user_id', $user->id)
            ->first();

        if ($ownedTeam) {
            $this->team = $ownedTeam;
            $this->isCaptain = true;
            return;
        }

        if ($user->team_id) {
            $memberTeam = Team::query()
                ->with('owner')
                ->find($user->team_id);

            if ($memberTeam) {
                $this->team = $memberTeam;
                $this->isCaptain = false;
                return;
            }
        }

        $this->team = null;
        $this->isCaptain = false;
        $this->user = $user;
    }

    public function getMembersProperty()
    {
        if (! $this->team) {
            return collect();
        }

        return User::query()
            ->where('team_id', $this->team->id)
            ->orderByRaw('id = ? desc', [$this->team->owner_user_id])
            ->orderBy('name')
            ->get();
    }

    public function getRemainingSlotsProperty(): int
    {
        if (! $this->team) {
            return 0;
        }

        $limit = auth()->user()->hasRole('enterprise') ? 999999 : 3;

        $count = User::query()
            ->where('team_id', $this->team->id)
            ->where('id', '!=', $this->team->owner_user_id)
            ->count();

        return max(0, $limit - $count);
    }

    public function getRecentActivitiesProperty()
    {
        if (! $this->team) {
            return collect();
        }

        return EmerionActivityLog::query()
            ->where('team_id', $this->team->id)
            ->latest()
            ->take(8)
            ->get();
    }

    public function getProvidersProperty()
    {
        if (! $this->team) {
            return collect();
        }

        return TeamProvider::query()
            ->where('team_id', $this->team->id)
            ->latest()
            ->get();
    }

    public function getLatestStatusesProperty()
    {
        if (! $this->team) {
            return collect();
        }

        return $this->members->map(function ($member) {
            $latest = MemberStatusUpdate::query()
                ->where('team_id', $this->team->id)
                ->where('user_id', $member->id)
                ->latest()
                ->first();

            return [
                'user' => $member,
                'status' => $latest?->status ?? 'no_status',
                'note' => $latest?->note,
                'updated_at' => $latest?->created_at,
            ];
        });
    }

    public function getLatestLocationsProperty()
    {
        if (! $this->team) {
            return collect();
        }

        return $this->members->map(function ($member) {
            $latest = MemberLocation::query()
                ->where('team_id', $this->team->id)
                ->where('user_id', $member->id)
                ->latest('recorded_at')
                ->first();

            return [
                'user' => $member,
                'address' => $latest?->address,
                'latitude' => $latest?->latitude,
                'longitude' => $latest?->longitude,
                'recorded_at' => $latest?->recorded_at,
            ];
        });
    }

    public function getGroupScanLogsProperty()
    {
        if (! $this->team) {
            return collect();
        }

        $memberIds = User::query()
            ->where('team_id', $this->team->id)
            ->pluck('id');

        return ScanLog::query()
            ->with(['profile.user', 'asset'])
            ->whereHas('profile', function ($query) use ($memberIds) {
                $query->whereIn('user_id', $memberIds);
            })
            ->latest()
            ->take(15)
            ->get();
    }

    public function getMedicalSummariesProperty()
    {
        if (! $this->team) {
            return collect();
        }

        return $this->members->map(function ($member) {
            $profile = EmergencyProfile::query()
                ->where('user_id', $member->id)
                ->first();

            $allergies = "";
            $currentMedications = "";
            $medicalConditions = "";

            foreach ($profile->allergies as $allergy) {
                $allergies .= $allergy . ",";
            }

            foreach ($profile->current_medications as $current_medication) {
                $currentMedications .= $current_medication . ",";
            }

            foreach ($profile->medical_conditions as $medical_conditions) {
                $medicalConditions .= $medical_conditions . ",";
            }

            return [
                'user' => $member,
                'blood_type' => $profile->blood_type ?? null,
                'allergies' => rtrim($allergies, ',') ?? null,
                'current_medications' => rtrim($currentMedications, ',') ?? null,
                'medical_conditions' => rtrim($medicalConditions, ',') ?? null,
                'primary_physician_name' => $profile->primary_physician_name ?? null,
            ];
        });
    }

    public function createTeam(): void
    {
        $user = Auth::user();

        abort_unless($user, 403);
        abort_unless($user->hasRole(['premium', 'enterprise']), 403);

        if ($user->team_id || Team::where('owner_user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'team_name' => 'You already belong to or own a group.',
            ]);
        }

        $validated = $this->validate([
            'team_name' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        $team = DB::transaction(function () use ($user, $validated) {
            $baseSlug = Str::slug($validated['team_name']) ?: 'group';
            $slug = $baseSlug;
            $counter = 1;

            while (Team::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $team = Team::create([
                'name' => $validated['team_name'],
                'slug' => $slug,
                'owner_user_id' => $user->id,
            ]);

            $user->team_id = $team->id;
            $user->save();

            if (class_exists(TeamActivity::class)) {
                TeamActivity::log(
                    team: $team,
                    type: 'team_created',
                    title: 'Group created',
                    description: $user->name . ' created the group.',
                    user: $user
                );
            } else {
                EmerionActivityLog::create([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'type' => 'team_created',
                    'title' => 'Group created',
                    'description' => $user->name . ' created the group.',
                ]);
            }

            return $team;
        });

        $this->team = Team::query()->with('owner')->find($team->id);
        $this->isCaptain = true;
        $this->reset('team_name');

        session()->flash('success', 'Group created successfully.');
    }

    public function addMember(): void
    {
        $owner = Auth::user();

        abort_unless($owner, 403);
        abort_unless($this->isCaptain, 403);
        abort_unless($this->team, 404);

        $this->validate([
            'email' => ['required', 'email'],
        ]);

        $limit = $owner->hasRole('enterprise') ? 999999 : 3;

        $linkedCount = User::query()
            ->where('team_id', $this->team->id)
            ->where('id', '!=', $this->team->owner_user_id)
            ->count();

        if ($linkedCount >= $limit) {
            throw ValidationException::withMessages([
                'email' => $owner->hasRole('enterprise')
                    ? 'Group member limit reached.'
                    : 'Premium plan allows only up to 3 linked accounts.',
            ]);
        }

        $member = User::query()
            ->where('email', $this->email)
            ->first();

        if (! $member) {
            throw ValidationException::withMessages([
                'email' => 'User not found.',
            ]);
        }

        if ($member->id === $owner->id) {
            throw ValidationException::withMessages([
                'email' => 'You cannot add your own account.',
            ]);
        }

        if (!$member->hasRole(['premium', 'enterprise'])) {
            throw ValidationException::withMessages([
                'email' => 'Only premium or enterprise accounts can be linked.',
            ]);
        }

        if ($member->team_id && $member->team_id !== $this->team->id) {
            throw ValidationException::withMessages([
                'email' => 'This account already belongs to another group.',
            ]);
        }

        if ((int) $member->team_id === (int) $this->team->id) {
            throw ValidationException::withMessages([
                'email' => 'This account is already in your group.',
            ]);
        }

        $member->team_id = $this->team->id;
        $member->save();

        if (class_exists(TeamActivity::class)) {
            TeamActivity::log(
                team: $this->team,
                type: 'member_added',
                title: 'Member added',
                description: $owner->name . ' added ' . $member->name . ' to the group.',
                user: $owner,
                metadata: ['member_id' => $member->id]
            );
        } else {
            EmerionActivityLog::create([
                'team_id' => $this->team->id,
                'user_id' => $owner->id,
                'type' => 'member_added',
                'title' => 'Member added',
                'description' => $owner->name . ' added ' . $member->name . ' to the group.',
                'metadata' => ['member_id' => $member->id],
            ]);
        }

        $this->reset('email');

        session()->flash('success', 'Member added successfully.');
    }

    public function removeMember(int $userId): void
    {
        $owner = Auth::user();

        abort_unless($owner, 403);
        abort_unless($this->isCaptain, 403);
        abort_unless($this->team, 404);

        $member = User::query()
            ->where('id', $userId)
            ->where('team_id', $this->team->id)
            ->where('id', '!=', $this->team->owner_user_id)
            ->firstOrFail();

        $memberName = $member->name;

        $member->team_id = null;
        $member->save();

        if (class_exists(TeamActivity::class)) {
            TeamActivity::log(
                team: $this->team,
                type: 'member_removed',
                title: 'Member removed',
                description: $owner->name . ' removed ' . $memberName . ' from the group.',
                user: $owner,
                metadata: ['member_id' => $userId]
            );
        } else {
            EmerionActivityLog::create([
                'team_id' => $this->team->id,
                'user_id' => $owner->id,
                'type' => 'member_removed',
                'title' => 'Member removed',
                'description' => $owner->name . ' removed ' . $memberName . ' from the group.',
                'metadata' => ['member_id' => $userId],
            ]);
        }

        session()->flash('success', 'Member removed successfully.');
    }

    public function addProvider(): void
    {
        $user = Auth::user();

        abort_unless($user, 403);
        abort_unless($this->isCaptain, 403);
        abort_unless($this->team, 404);

        $validated = $this->validate([
            'provider_type' => ['required', 'string', 'max:50'],
            'provider_name' => ['required', 'string', 'max:255'],
            'provider_phone' => ['nullable', 'string', 'max:255'],
            'provider_email' => ['nullable', 'email', 'max:255'],
            'provider_address' => ['nullable', 'string', 'max:255'],
            'provider_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        TeamProvider::create([
            'team_id' => $this->team->id,
            'type' => $validated['provider_type'],
            'name' => $validated['provider_name'],
            'phone' => $validated['provider_phone'],
            'email' => $validated['provider_email'],
            'address' => $validated['provider_address'],
            'notes' => $validated['provider_notes'],
            'created_by' => $user->id,
        ]);

        if (class_exists(TeamActivity::class)) {
            TeamActivity::log(
                team: $this->team,
                type: 'provider_added',
                title: 'Care provider added',
                description: $user->name . ' added provider ' . $validated['provider_name'] . '.',
                user: $user
            );
        } else {
            EmerionActivityLog::create([
                'team_id' => $this->team->id,
                'user_id' => $user->id,
                'type' => 'provider_added',
                'title' => 'Care provider added',
                'description' => $user->name . ' added provider ' . $validated['provider_name'] . '.',
            ]);
        }

        $this->reset(
            'provider_type',
            'provider_name',
            'provider_phone',
            'provider_email',
            'provider_address',
            'provider_notes'
        );

        $this->provider_type = 'doctor';

        session()->flash('success', 'Care provider added successfully.');
    }

    public function saveStatus(): void
    {
        $user = Auth::user();

        abort_unless($user, 403);
        abort_unless($this->team, 404);
        abort_unless((int) $user->team_id === (int) $this->team->id, 403);

        $validated = $this->validate([
            'status' => ['required', 'in:safe,need_help,emergency,offline,on_the_way'],
            'status_note' => ['nullable', 'string', 'max:500'],
        ]);

        MemberStatusUpdate::create([
            'team_id' => $this->team->id,
            'user_id' => $user->id,
            'status' => $validated['status'],
            'note' => $validated['status_note'],
        ]);

        if (class_exists(TeamActivity::class)) {
            TeamActivity::log(
                team: $this->team,
                type: 'status_updated',
                title: 'Status updated',
                description: $user->name . ' changed status to ' . str_replace('_', ' ', $validated['status']) . '.',
                user: $user
            );
        } else {
            EmerionActivityLog::create([
                'team_id' => $this->team->id,
                'user_id' => $user->id,
                'type' => 'status_updated',
                'title' => 'Status updated',
                'description' => $user->name . ' changed status to ' . str_replace('_', ' ', $validated['status']) . '.',
            ]);
        }

        $this->reset('status_note');
        $this->status = 'safe';

        session()->flash('success', 'Status updated.');
    }
};
?>

@volt('group')
<div>
    <x-layouts.app>
        <div class="mx-auto max-w-full px-4 py-8">
            <div class="space-y-6">
                @if (session('success'))
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if (! $this->team)
                    <div class="rounded-2xl border bg-white p-6 shadow-sm">
                        <h1 class="text-2xl font-semibold">Create Your Group Dashboard</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Create your group first to manage members, group scan logs, care providers, status updates, and shared safety information.
                        </p>

                        <form wire:submit="createTeam" class="mt-6 max-w-xl space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Group Name</label>
                                <input
                                    type="text"
                                    wire:model="team_name"
                                    class="mt-1 block w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm"
                                    placeholder="Enter your group name"
                                >
                                @error('team_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <button
                                type="submit"
                                class="rounded-xl bg-black px-4 py-2.5 text-sm font-medium text-white"
                            >
                                Create Group
                            </button>
                        </form>
                    </div>
                @else
                    <div class="grid gap-6 lg:grid-cols-3">
                        <div class="rounded-2xl border bg-white p-6 shadow-sm">
                            <div class="text-sm text-gray-500">Group Name</div>
                            <div class="mt-1 text-xl font-semibold">{{ $this->team->name }}</div>
                            <div class="mt-2 text-sm text-gray-600">
                                @if ($this->isCaptain)
                                    You are the captain of this group.
                                @else
                                    You are a member of this group.
                                @endif
                            </div>
                        </div>

                        <div class="rounded-2xl border bg-white p-6 shadow-sm">
                            <div class="text-sm text-gray-500">Members</div>
                            <div class="mt-1 text-2xl font-semibold">{{ $this->members->count() }}</div>
                            <div class="mt-2 text-sm text-gray-600">
                                Remaining slots: {{ $this->remainingSlots }}
                            </div>
                        </div>

                        <div class="rounded-2xl border bg-white p-6 shadow-sm">
                            <div class="text-sm text-gray-500">Captain</div>
                            <div class="mt-1 text-xl font-semibold">{{ $this->team->owner?->name ?? 'Unknown' }}</div>
                            <div class="mt-2 text-sm text-gray-600">
                                {{ $this->team->owner?->email }}
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-3">
                        <div class="space-y-6 lg:col-span-2">
                            <div class="rounded-2xl border bg-white p-6 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-lg font-semibold">Group Members</h2>
                                    @if ($this->isCaptain)
                                        <span class="text-sm text-gray-500">Captain controls member management</span>
                                    @endif
                                </div>

                                @if ($this->isCaptain)
                                    <form wire:submit="addMember" class="mt-4 grid gap-3 md:grid-cols-[1fr_auto]">
                                        <div>
                                            <input
                                                type="email"
                                                wire:model="email"
                                                class="block w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm"
                                                placeholder="Enter member email"
                                            >
                                            @error('email')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <button
                                            type="submit"
                                            class="rounded-xl bg-black px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50"
                                            {{ $this->remainingSlots <= 0 ? 'disabled' : '' }}
                                        >
                                            Add Member
                                        </button>
                                    </form>
                                @endif

                                <div class="mt-6 space-y-3">
                                    @forelse ($this->members as $member)
                                        <div class="rounded-xl border px-4 py-4">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <div class="font-medium text-gray-900">{{ $member->name }}</div>
                                                        @if ($member->id === $this->team->owner_user_id)
                                                            <span class="rounded-full bg-black px-2 py-0.5 text-xs text-white">
                                                                Captain
                                                            </span>
                                                        @elseif ($member->id === Auth::user()->id)
                                                            <span class="rounded-full bg-red-600 px-2 py-0.5 text-xs text-white">
                                                                You
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="mt-1 text-sm text-gray-600">{{ $member->email }}</div>
                                                    <div class="mt-1 text-xs uppercase tracking-wide text-gray-400">{{ $member->role }}</div>
                                                </div>

                                                @if ($this->isCaptain && $member->id !== $this->team->owner_user_id)
                                                    <button
                                                        type="button"
                                                        wire:click="removeMember({{ $member->id }})"
                                                        class="rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white"
                                                    >
                                                        Remove
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-600">No members yet.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-2xl border bg-white p-6 shadow-sm">
                                <h2 class="text-lg font-semibold">Shared Activity Logs</h2>

                                <div class="mt-4 space-y-3">
                                    @forelse ($this->recentActivities as $activity)
                                        <div class="rounded-xl border px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $activity->title }}</div>
                                            @if ($activity->description)
                                                <div class="mt-1 text-sm text-gray-600">{{ $activity->description }}</div>
                                            @endif
                                            <div class="mt-2 text-xs text-gray-400">
                                                {{ $activity->created_at?->diffForHumans() }}
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-600">No activity yet.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-2xl border bg-white p-6 shadow-sm">
                                <h2 class="text-lg font-semibold">Group Scan Logs</h2>

                                <div class="mt-4 space-y-3">
                                    @forelse ($this->groupScanLogs as $log)
                                        <div class="rounded-xl border px-4 py-3">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <div class="font-medium text-gray-900">
                                                        {{ $log->profile?->user?->name ?? 'Unknown User' }}
                                                    </div>

                                                    <div class="mt-1 text-sm text-gray-600">
                                                        {{ $log->trigger === 'nfc_tap' ? 'NFC Tap' : 'QR Scan' }}
                                                    </div>

                                                    @if ($log->location_text)
                                                        <div class="mt-1 text-sm text-gray-600">
                                                            Location: {{ $log->location_text }}
                                                        </div>
                                                    @elseif ($log->lat && $log->lng)
                                                        <div class="mt-1 text-sm text-gray-600">
                                                            Coordinates: {{ $log->lat }}, {{ $log->lng }}
                                                        </div>
                                                    @else
                                                        <div class="mt-1 text-sm text-gray-500">
                                                            No location captured
                                                        </div>
                                                    @endif

                                                    @if ($log->lat && $log->lng)
                                                        <div class="mt-2">
                                                            <a
                                                                href="https://www.google.com/maps?q={{ $log->lat }},{{ $log->lng }}"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                class="inline-flex items-center rounded-lg border px-3 py-1.5 text-sm font-medium text-blue-600 hover:bg-gray-50"
                                                            >
                                                                Open in Google Maps
                                                            </a>
                                                        </div>
                                                    @endif

                                                    @if ($log->asset)
                                                        <div class="mt-1 text-xs text-gray-400">
                                                            Asset: {{ $log->asset->name ?? ('#'.$log->asset->id) }}
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="text-xs text-gray-400">
                                                    {{ $log->created_at?->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-600">No group scan logs yet.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-2xl border bg-white p-6 shadow-sm">
                                <h2 class="text-lg font-semibold">Latest Locations</h2>

                                <div class="mt-4 space-y-3">
                                    @forelse ($this->latestLocations as $item)
                                        <div class="rounded-xl border px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $item['user']->name }}</div>

                                            <div class="mt-1 text-sm text-gray-600">
                                                {{ $item['address'] ?: 'No location recorded yet.' }}
                                            </div>

                                            @if ($item['latitude'] && $item['longitude'])
                                                <div class="mt-1 text-xs text-gray-400">
                                                    {{ $item['latitude'] }}, {{ $item['longitude'] }}
                                                </div>
                                                <div class="mt-2">
                                                    <a
                                                        href="https://www.google.com/maps?q={{ $item['latitude'] }},{{ $item['longitude'] }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="inline-flex items-center rounded-lg border px-3 py-1.5 text-sm font-medium text-blue-600 hover:bg-gray-50"
                                                    >
                                                        Open in Google Maps
                                                    </a>
                                                </div>
                                            @endif

                                            <div class="mt-2 text-xs text-gray-400">
                                                {{ $item['recorded_at'] ? $item['recorded_at']->diffForHumans() : 'No timestamp' }}
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-600">No locations available.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="rounded-2xl border bg-white p-6 shadow-sm">
                                <h2 class="text-lg font-semibold">Check-In Status</h2>

                                <form wire:submit="saveStatus" class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">My Status</label>
                                        <select
                                            wire:model="status"
                                            class="mt-1 block w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm"
                                        >
                                            <option value="safe">Safe</option>
                                            <option value="need_help">Need Help</option>
                                            <option value="emergency">Emergency</option>
                                            <option value="offline">Offline</option>
                                            <option value="on_the_way">On The Way</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Note</label>
                                        <textarea
                                            wire:model="status_note"
                                            rows="3"
                                            class="mt-1 block w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm"
                                            placeholder="Optional note"
                                        ></textarea>
                                        @error('status_note')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button
                                        type="submit"
                                        class="rounded-xl bg-black px-4 py-2.5 text-sm font-medium text-white"
                                    >
                                        Update Status
                                    </button>
                                </form>

                                <div class="mt-6 space-y-3">
                                    @forelse ($this->latestStatuses as $item)
                                        <div class="rounded-xl border px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $item['user']->name }}</div>
                                            <div class="mt-1 text-sm capitalize text-gray-700">
                                                {{ str_replace('_', ' ', $item['status']) }}
                                            </div>

                                            @if ($item['note'])
                                                <div class="mt-1 text-sm text-gray-600">{{ $item['note'] }}</div>
                                            @endif

                                            <div class="mt-2 text-xs text-gray-400">
                                                {{ $item['updated_at'] ? $item['updated_at']->diffForHumans() : 'No update yet' }}
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-600">No status updates yet.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-2xl border bg-white p-6 shadow-sm">
                                <h2 class="text-lg font-semibold">Care Providers</h2>

                                @if ($this->isCaptain)
                                    <form wire:submit="addProvider" class="mt-4 space-y-3">
                                        <select
                                            wire:model="provider_type"
                                            class="block w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm"
                                        >
                                            <option value="doctor">Doctor</option>
                                            <option value="hospital">Hospital</option>
                                            <option value="pharmacy">Pharmacy</option>
                                            <option value="caregiver">Caregiver</option>
                                            <option value="insurance">Insurance</option>
                                        </select>

                                        <input
                                            wire:model="provider_name"
                                            type="text"
                                            class="block w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm"
                                            placeholder="Provider name"
                                        >

                                        <input
                                            wire:model="provider_phone"
                                            type="text"
                                            class="block w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm"
                                            placeholder="Phone"
                                        >

                                        <input
                                            wire:model="provider_email"
                                            type="email"
                                            class="block w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm"
                                            placeholder="Email"
                                        >

                                        <input
                                            wire:model="provider_address"
                                            type="text"
                                            class="block w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm"
                                            placeholder="Address"
                                        >

                                        <textarea
                                            wire:model="provider_notes"
                                            rows="3"
                                            class="block w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm"
                                            placeholder="Notes"
                                        ></textarea>

                                        @error('provider_name')
                                            <p class="text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('provider_email')
                                            <p class="text-sm text-red-600">{{ $message }}</p>
                                        @enderror

                                        <button
                                            type="submit"
                                            class="rounded-xl bg-black px-4 py-2.5 text-sm font-medium text-white"
                                        >
                                            Add Provider
                                        </button>
                                    </form>
                                @endif

                                <div class="mt-6 space-y-3">
                                    @forelse ($this->providers as $provider)
                                        <div class="rounded-xl border px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="font-medium text-gray-900">{{ $provider->name }}</div>
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs uppercase text-gray-600">
                                                    {{ $provider->type }}
                                                </span>
                                            </div>

                                            @if ($provider->phone)
                                                <div class="mt-1 text-sm text-gray-600">{{ $provider->phone }}</div>
                                            @endif

                                            @if ($provider->email)
                                                <div class="mt-1 text-sm text-gray-600">{{ $provider->email }}</div>
                                            @endif

                                            @if ($provider->address)
                                                <div class="mt-1 text-sm text-gray-600">{{ $provider->address }}</div>
                                            @endif

                                            @if ($provider->notes)
                                                <div class="mt-2 text-sm text-gray-500">{{ $provider->notes }}</div>
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-600">No care providers added yet.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-2xl border bg-white p-6 shadow-sm">
                                <h2 class="text-lg font-semibold">Shared Medical Snapshot</h2>

                                <div class="mt-4 space-y-4">
                                    @if ($this->medicalSummaries)
                                    @forelse ($this->medicalSummaries as $item)
                                        <div class="rounded-xl border px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $item['user']->name }}</div>

                                            <div class="mt-2 space-y-1 text-sm text-gray-600">
                                                <div><span class="font-medium text-gray-800">Blood Type:</span> {{ $item['blood_type'] ?: '—' }}</div>
                                                <div><span class="font-medium text-gray-800">Allergies:</span> {{ $item['allergies'] ?: '—' }}</div>
                                                <div><span class="font-medium text-gray-800">Medications:</span> {{ $item['current_medications'] ?: '—' }}</div>
                                                <div><span class="font-medium text-gray-800">Conditions:</span> {{ $item['medical_conditions'] ?: '—' }}</div>
                                                <div><span class="font-medium text-gray-800">Physician Name:</span> {{ $item['primary_physician_name'] ?: '—' }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-600">No medical summaries available yet.</p>
                                    @endforelse
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-layouts.app>
</div>
@endvolt
