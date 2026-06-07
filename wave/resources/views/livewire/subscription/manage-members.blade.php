<div>
    <h3>Connected Users</h3>

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    @if (session('error'))
        <p style="color: red;">{{ session('error') }}</p>
    @endif

    <input type="email" wire:model="email" placeholder="Enter email">
    <button type="button"
            wire:click="addMember"
            class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
        <span class="text-lg leading-none">+</span>
        <span>Add User</span>
    </button>

    <ul>
        @foreach ($members as $member)
            <li>
                {{ $member->email }}
                <button class="btn btn-danger" wire:click="removeMember({{ $member->id }})">
                    Remove
                </button>
                <button type="button"
                        wire:click="removeMember({{ $member->id }})"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    <span class="text-lg leading-none">-</span>
                    <span>Remove User</span>
                </button>
            </li>
        @endforeach
    </ul>
</div>