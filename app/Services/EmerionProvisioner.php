<?php

namespace App\Services;

use App\Models\User;
use App\Models\EmergencyProfile;
use App\Models\SafeAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmerionProvisioner
{
    /**
     * Provision Emerion essentials for a user:
     * - Ensure EmergencyProfile exists
     * - Ensure default SafeAsset exists (QR sticker)
     * - Optionally generate QR PNG
     */
    public function provisionFor(User $user, array $options = []): array
    {
        $options = array_merge([
            'create_default_asset' => true,
            'default_asset_type'   => 'qr_sticker',
            'generate_qr_png'      => true, // set true if you install a QR library
            'force_new_asset'      => false, // useful for replacements
        ], $options);

        return DB::transaction(function () use ($user, $options) {

            // 1) Ensure EmergencyProfile exists
            $profile = $user->emergencyProfile()->first();

            if (!$profile) {
                $profile = $user->emergencyProfile()->create([
                    'uuid'      => (string) Str::uuid(),
                    'country'   => 'PH',
                    'is_public' => true,
                    'is_active' => true,
                ]);
            }

            // 2) Ensure default asset exists
            $asset = null;

            if ($options['create_default_asset']) {
                if (!$options['force_new_asset']) {
                    $asset = SafeAsset::query()
                        ->where('profile_id', $profile->id)
                        ->whereNull('deactivated_at')
                        ->where('type', $options['default_asset_type'])
                        ->first();
                }

                if (!$asset) {
                    // If creating a new primary asset, unset others
                    SafeAsset::where('profile_id', $profile->id)->update(['is_primary' => false]);

                    $asset = SafeAsset::create([
                        'profile_id' => $profile->id,
                        'type'       => $options['default_asset_type'],
                        'is_primary' => true,
                        'owner_user_id' => $user->id,
                    ]);
                }
            }

            // 3) Optionally generate QR PNG
            if ($asset && $options['generate_qr_png']) {
                $qrPath = $this->generateQrPngForAsset($asset);
                $asset->update(['qr_path' => $qrPath]);
            }

            return [
                'profile' => $profile,
                'asset'   => $asset,
            ];
        });
    }

    /**
     * Generates a QR PNG file (requires a QR library; see section D).
     */
    public function generateQrPngForAsset(SafeAsset $asset): string
    {
        // $url = route('scan', ['public_token' => $asset->public_token]);
        $url = env('APP_URL') . '/scan/' . $asset->public_token;
        // We’ll store in: storage/app/public/qrcodes/{token}.png
        $path = "public/qrcodes/{$asset->public_token}.png";

        // This method expects you to install a QR package (see D).
        // Replace the line below depending on your chosen library.
        $pngBinary = app(QrPngGenerator::class)->make($url);

        Storage::put($path, $pngBinary);

        // Return public path (for Storage::url)
        return $path;
    }

    public function ensureEntitlementsForUser(\App\Models\User $user, array $entitlements): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $entitlements) {

            // Ensure profile exists
            $profile = $user->emergencyProfile()->first();
            if (!$profile) {
                $profile = $user->emergencyProfile()->create([
                    'uuid'      => (string) \Illuminate\Support\Str::uuid(),
                    'country'   => 'PH',
                    'is_public' => true,
                    'is_active' => true,
                ]);
            }

            $required = $entitlements['assets'] ?? [];

            foreach ($required as $type => $qty) {
                $activeCount = \App\Models\SafeAsset::query()
                    ->where('profile_id', $profile->id)
                    ->where('type', $type)
                    ->whereNull('deactivated_at')
                    ->count();

                // Create missing assets (idempotent)
                $missing = max(0, (int)$qty - (int)$activeCount);

                for ($i = 0; $i < $missing; $i++) {
                    \App\Models\SafeAsset::create([
                        'profile_id' => $profile->id,
                        'type'       => $type,
                        'is_primary' => false,
                    ]);
                }
            }

            // Optional: if downgraded, deactivate extras (do NOT delete)
            $allowedTypes = array_keys($required);

            $allActive = \App\Models\SafeAsset::query()
                ->where('profile_id', $profile->id)
                ->whereNull('deactivated_at')
                ->get()
                ->groupBy('type');

            foreach ($allActive as $type => $assets) {
                $allowedQty = (int)($required[$type] ?? 0);

                // If type not allowed at all, deactivate all of that type
                if (!in_array($type, $allowedTypes, true)) {
                    foreach ($assets as $a) {
                        $a->update(['deactivated_at' => now(), 'is_primary' => false]);
                    }
                    continue;
                }

                // If more than allowed, deactivate extras (keep oldest active)
                if ($assets->count() > $allowedQty) {
                    $extras = $assets->sortBy('id')->slice($allowedQty);
                    foreach ($extras as $a) {
                        $a->update(['deactivated_at' => now(), 'is_primary' => false]);
                    }
                }
            }

            // Ensure there is a primary asset (prefer qr_sticker)
            $primary = \App\Models\SafeAsset::query()
                ->where('profile_id', $profile->id)
                ->whereNull('deactivated_at')
                ->where('is_primary', true)
                ->first();

            if (!$primary) {
                \App\Models\SafeAsset::where('profile_id', $profile->id)->update(['is_primary' => false]);

                $pick = \App\Models\SafeAsset::query()
                    ->where('profile_id', $profile->id)
                    ->whereNull('deactivated_at')
                    ->orderByRaw("CASE WHEN type='qr_sticker' THEN 0 WHEN type='nfc_card' THEN 1 ELSE 2 END")
                    ->first();

                if ($pick) $pick->update(['is_primary' => true]);
            }
        });
    }

}
