<?php

namespace App\Filament\Resources\Batch;

use App\Filament\Resources\Batch\Pages;
use App\Models\Batch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;
use BackedEnum;
use App\Filament\Resources\Batch\Schemas\BatchForm;
use Filament\Schemas\Schema;
use App\Filament\Resources\Batch\Tables\BatchTable;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';
    protected static string|UnitEnum|null $navigationGroup = 'Emerion';

    public static function form(Schema $schema): Schema
    {
        return BatchForm::configure($schema->columns(1));
    }

    public static function table(Table $table): Table
    {
        return BatchTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBatches::route('/'),
            'create' => Pages\CreateBatch::route('/create'),
            'edit'   => Pages\EditBatch::route('/{record}/edit'),
        ];
    }
}