<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use App\Services\EmerionProvisioner;

class ProvisionEmerionOnRegister
{
    public function __construct(private EmerionProvisioner $provisioner) {}

    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Create profile + default QR sticker asset
        $this->provisioner->provisionFor($user, [
            'create_default_asset' => true,
            'default_asset_type'   => 'qr_sticker',
            'generate_qr_png'      => true, // set true after QR lib install
        ]);
    }
}
