<?php

namespace App\Observers;

use App\Services\EmerionProvisioner;
use App\Support\EmerionPlans;

class WaveSubscriptionObserver
{
    public function __construct(private EmerionProvisioner $provisioner) {}

    public function saved($subscription): void
    {
        $user = $subscription->user ?? null;
        if (!$user) return;

        $isActive = $this->isSubscriptionActive($subscription);
        $planSlug = $this->getPlanSlug($subscription);

        $entitlements = EmerionPlans::entitlementsForPlan($isActive ? $planSlug : null);

        $this->provisioner->ensureEntitlementsForUser($user, $entitlements);
    }

    public function deleted($subscription): void
    {
        $user = $subscription->user ?? null;
        if (!$user) return;

        $entitlements = EmerionPlans::entitlementsForPlan(null);
        $this->provisioner->ensureEntitlementsForUser($user, $entitlements);
    }

    private function isSubscriptionActive($s): bool
    {
        // 1) If Wave exposes a method, use it
        foreach (['valid', 'active', 'isActive'] as $method) {
            if (method_exists($s, $method)) {
                try {
                    $val = $s->{$method}();
                    if (is_bool($val)) return $val;
                } catch (\Throwable $e) {}
            }
        }

        // 2) Common boolean/status fields
        if (isset($s->active) && is_bool($s->active)) return $s->active;

        if (isset($s->status)) {
            $status = strtolower((string) $s->status);
            if (in_array($status, ['active', 'trialing'], true)) return true;
            if (in_array($status, ['canceled', 'cancelled', 'incomplete', 'expired'], true)) return false;
        }

        // 3) Date-based fallback
        // - ends_at in future => active
        if (isset($s->ends_at) && $s->ends_at) {
            return now()->lt($s->ends_at);
        }

        // - canceled_at set => not active
        if (isset($s->canceled_at) && $s->canceled_at) return false;

        // Default: assume not active
        return false;
    }

    private function getPlanSlug($s): ?string
    {
        // Try related plan first
        if (isset($s->plan) && $s->plan) {
            if (isset($s->plan->slug) && $s->plan->slug) return (string) $s->plan->slug;
            if (isset($s->plan->name) && $s->plan->name) return (string) $s->plan->name;
        }

        // Try direct fields
        foreach (['plan_slug', 'plan', 'plan_id', 'stripe_plan'] as $field) {
            if (isset($s->{$field}) && $s->{$field}) return (string) $s->{$field};
        }

        return null;
    }
}
