<?php

namespace Wave\Http\Livewire\Subscription;

use Livewire\Component;
use App\Models\User;

class ManageMembers extends Component
{
    public $email;

    public function addMember()
    {
        $owner = auth()->user();

        if (!$owner->isPremium()) {
            session()->flash('error', 'You need premium');
            return;
        }

        $member = User::where('email', $this->email)->first();

        if (!$member) {
            session()->flash('error', 'User not found');
            return;
        }

        if ($owner->subscription_expires_at < now()) {
            return false;
        }

        if ($member->id === $owner->id) {
            session()->flash('error', 'Invalid user');
            return;
        }

        if ($member->subscription_owner_id) {
            session()->flash('error', 'Already in a subscription');
            return;
        }

        if ($owner->members()->count() >= 2) {
            session()->flash('error', 'Max members reached');
            return;
        }

        if ($member->subscription_owner_id && $member->subscription_owner_id !== $owner->id) {
            session()->flash('error', 'User already belongs to another subscription');
            return;
        }

        if ($member->hasRole('solo')) {
            session()->flash('error', 'Solo users must cancel subscription first');
            return;
        }

        $member->update([
            'subscription_owner_id' => $owner->id,
            'subscription_expires_at' => $owner->subscription_expires_at
        ]);

        $member->syncRoles(['premium']);

        $this->reset('email');

        session()->flash('success', 'Member added');
    }

    public function removeMember($id)
    {
        $owner = auth()->user();

        $member = User::findOrFail($id);

        if ($member->subscription_owner_id !== $owner->id) {
            abort(403);
        }

        $member->update([
            'subscription_owner_id' => null,
            'subscription_expires_at' => null
        ]);
    }

    public function render()
    {
        $owner = auth()->user()->getSubscriptionOwner();
    
        return view('wave::livewire.subscription.manage-members', [
            'members' => $owner->members
        ]);
    }
}