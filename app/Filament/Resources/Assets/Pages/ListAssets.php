<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Asset'),
        ];
    }
}