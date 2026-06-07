<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}