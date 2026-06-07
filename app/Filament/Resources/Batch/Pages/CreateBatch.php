<?php

namespace App\Filament\Resources\Batch\Pages;

use App\Filament\Resources\Batch\BatchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBatch extends CreateRecord
{
    protected static string $resource = BatchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['remaining'] = $data['total_assets'];
        $data['created_by'] = auth()->id();
        return $data;
    }
}