<?php

namespace App\Filament\Resources\Assets;

use App\Filament\Resources\Assets\Pages;
use App\Models\SafeAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;
use App\Filament\Resources\Assets\Schemas\AssetForm;
use Filament\Schemas\Schema;
use App\Filament\Resources\Assets\Tables\AssetsTable;

class AssetsResource extends Resource
{
    protected static ?string $model = SafeAsset::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';
    protected static string|UnitEnum|null $navigationGroup = 'Emerion';

    public static function form(Schema $schema): Schema
    {
        return AssetForm::configure($schema->columns(1));
    }

    public static function table(Table $table): Table
    {
        return AssetsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit'   => Pages\EditAsset::route('/{record}/edit'),
        ];
    }
}