<?php

namespace App\Filament\Resources\Batch\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Str;

class BatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Batch Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('code')
                            ->required()
                            ->maxLength(64)
                            ->unique(ignoreRecord: true),
                        
                        TextInput::make('total_assets')
                            ->required(),
                            
                        Select::make('validity')
                            ->options([
                                '12' => '1 year',
                                '24' => '2 years',
                                '36' => '3 years',
                                '48' => '4 years',
                                '60' => '5 years',
                            ])
                            ->nullable(),

                        Select::make('asset_type')
                            ->options([
                                'qr_sticker' => 'Sticker',
                                'qr_card' => 'Card',
                                'nfc_card' => 'NFC',
                            ])
                            ->nullable(),

                        Textarea::make('notes')
                            ->rows(3)
                            ->nullable(),

                        DateTimePicker::make('printed_at')
                            ->nullable(),
                    ])
                    ->columns(3)
            ]);
    }
}
