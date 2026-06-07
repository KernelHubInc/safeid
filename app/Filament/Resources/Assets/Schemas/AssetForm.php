<?php

namespace App\Filament\Resources\Assets\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Str;
use App\Models\Batch;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asset Management')
                    ->schema([
                        TextInput::make('id')
                        ->label('ID')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn ($record) => filled($record)),

                        TextInput::make('public_token')
                            ->required()
                            ->maxLength(64),

                        Select::make('batch_id')
                            ->label('Batch')
                            ->options(fn () => Batch::query()->orderBy('created_at', 'desc')->pluck('code', 'id'))
                            ->searchable()
                            ->nullable(),

                        Select::make('status')
                            ->options([
                                'generated' => 'Generated',
                                'packaged' => 'Packaged',
                                'sold' => 'Sold',
                                'activated' => 'Activated',
                                'registered' => 'Registered',
                                'disabled' => 'Disabled',
                            ])
                            ->required(),

                        Select::make('kit_plan')
                            ->label('Subscription')
                            ->options([
                                'solo' => 'Solo',
                                'premium' => 'Premium',
                                'smes' => 'SMEs',
                            ])
                            ->nullable(),

                        Select::make('owner_user_id')
                            ->label('Assigned User')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->nullable(),
                    ])
                    ->columns(3)
            ]);
    }
}
