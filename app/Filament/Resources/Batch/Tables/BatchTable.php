<?php

namespace App\Filament\Resources\Batch\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Support\Facades\Storage;
use Filament\Schemas\Components\Section;
use Filament\Tables\Action\ImportAction;


class BatchTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('asset_type')->badge(),
                TextColumn::make('total_assets')->label('Total Assets')->sortable(),
                TextColumn::make('generated')->label('Generated')->sortable(),
                TextColumn::make('remaining')->label('Remaining')->sortable(),
                TextColumn::make('printed_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->actions([
                EditAction::make()
            ])
            ->headerActions([])
            ->recordUrl(null)
            ->defaultSort('created_at', 'desc');
    }

    // protected static function viewInfoList(): array
    // {
    //     return [
    //         Section::make('Room Information')
    //         ->schema([
    //             TextEntry::make('room')->label('Room')->columns(1),
    //             TextEntry::make('bed')->label('Bed')->columns(1),
    //             TextEntry::make('price')->label('Price')->columns(1),
    //             TextEntry::make('max_capacity')->label('Max Capacity')->columns(1),
    //             TextEntry::make('status')->label('Status')->columns(1),
    //             TextEntry::make('room_type')->label('Room Type')->columns(1),
    //             TextEntry::make('rebates')->label('Agent Rebates')->columns(1),
    //             ImageEntry::make('image') // assumes $enrollment->photo is a URL or storage path
    //                 ->label('Image')
    //                 ->height('100%')
    //                 ->width('100%')
    //                 ->getStateUsing(fn ($record) => $record->image ? env('APP_URL').'/storage/' . $record->image : null),
    //                 ])->columns(1)
    //         ->columns(3)
    //     ];
    // }
}
