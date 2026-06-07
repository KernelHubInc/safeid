<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetsResource;
use App\Models\SafeAsset;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}