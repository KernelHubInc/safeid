<?php

namespace App\Support;

class EmerionPlans
{
    /**
     * Map Wave plan slug/name to entitlements.
     * Update the keys to match your Wave plan slugs.
     */
    public static function entitlementsForPlan(?string $planSlug): array
    {
        $planSlug = strtolower((string) $planSlug);

        return match ($planSlug) {
            'lite', 'solo', 'basic' => [
                'assets' => [
                    'qr_sticker' => 1,
                ],
                'contacts_limit' => 1,
            ],

            'connect', 'premium' => [
                'assets' => [
                    'qr_sticker' => 1,
                    'nfc_card'   => 1,
                ],
                'contacts_limit' => 5,
            ],

            'protect', 'enterprise' => [
                'assets' => [
                    'qr_sticker'  => 1,
                    'nfc_card'    => 1,
                    'crash_device'=> 1,
                ],
                'contacts_limit' => 20,
            ],

            default => [
                // No plan or unknown plan -> treat as Lite OR trial default
                'assets' => [
                    'qr_sticker' => 1,
                ],
                'contacts_limit' => 1,
            ],
        };
    }
}
