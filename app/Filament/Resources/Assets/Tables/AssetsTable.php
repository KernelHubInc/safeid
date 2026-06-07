<?php

namespace App\Filament\Resources\Assets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Support\Facades\Storage;
use Filament\Schemas\Components\Section;
use Filament\Tables\Action\ImportAction;
use App\Models\Batch;


class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Search QR ID or Token')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?: '—'),

                ImageColumn::make('qr_preview')
                    ->label('QR Preview')
                    ->getStateUsing(fn ($record) => route('qr-assets.qr.png', $record))
                    ->height(44)
                    ->width(44)
                    ->square(),

                TextColumn::make('public_token')
                    ->label('Public Token')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Token copied!')
                    ->limit(12)
                    ->tooltip(fn ($record) => $record->public_token),

                TextColumn::make('batch.code')
                    ->label('Batch Name')
                    ->sortable()
                    ->toggleable()
                    ->default('—'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'generated',
                        'gray' => 'packaged',
                        'warning' => 'sold',
                        'info' => 'activated',
                        'success' => 'registered',
                        'danger' => 'disabled',
                    ]),

                TextColumn::make('owner.name')
                    ->label('Assigned User')
                    ->default('None')
                    ->toggleable(),

                TextColumn::make('kit_plan')
                    ->label('Subscription')
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : '—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created Date')
                    ->date('M j, Y')
                    ->sortable(),

                TextColumn::make('scan_url')
                    ->label('Scan URL')
                    ->getStateUsing(fn ($record) => rtrim(config('app.url'), '/') . '/scan/' . $record->public_token)
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('batch_id')
                    ->label('All Batches')
                    ->options(fn () => Batch::query()->orderBy('created_at', 'desc')->pluck('code', 'id')),

                SelectFilter::make('status')
                    ->label('All Status')
                    ->options([
                        'generated' => 'Generated',
                        'packaged' => 'Packaged',
                        'sold' => 'Sold',
                        'activated' => 'Activated',
                        'registered' => 'Registered',
                        'disabled' => 'Disabled',
                    ]),

                SelectFilter::make('kit_plan')
                    ->label('All Subscriptions')
                    ->options([
                        'solo' => 'Solo',
                        'premium' => 'Premium',
                        'smes' => 'SMEs',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('printSelected')
                        ->label('Print Selected')
                        ->icon('heroicon-o-printer')
                        ->action(fn ($records) =>
                            redirect()->to('/print/assets?ids='.$records->pluck('id')->implode(','))
                        ),
                ]),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->icon('heroicon-o-eye')
                        ->tooltip('View')
                        ->url(fn ($record) => rtrim(config('app.url'), '/') . '/scan/' . $record->public_token)
                        ->openUrlInNewTab(),

                    // ⬇ Download QR PNG
                    Action::make('download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->tooltip('Download QR')
                        ->url(fn ($record) => route('qr-assets.qr.png', $record))
                        ->openUrlInNewTab(),

                    Action::make('print')
                        ->icon('heroicon-o-printer')
                        ->url(fn ($record) => route('print.asset', $record))
                        ->openUrlInNewTab(),

                    // 📋 Copy scan URL
                    Action::make('copyUrl')
                        ->icon('heroicon-o-clipboard')
                        ->tooltip('Copy URL')
                        ->modalHeading('Copy Scan URL')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalContent(fn ($record) => view('filament.assets.copy-url', [
                            'url' => rtrim(config('app.url'), '/') . '/scan/' . $record->public_token,
                        ])),

                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->tooltip('Edit'),

                    DeleteAction::make()
                        ->icon('heroicon-o-x-circle')
                        ->tooltip('Delete'),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('primary')
                ->button()
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
