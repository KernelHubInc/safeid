<?php

namespace App\Filament\Resources\Batch\Pages;

use App\Filament\Resources\Batch\BatchResource;
use App\Models\SafeAsset;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EditBatch extends EditRecord
{
    protected static string $resource = BatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateAssets')
                ->label('Generate QR Assets')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10000)
                        ->default(100)
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'generated' => 'generated',
                            'packaged' => 'packaged',
                        ])
                        ->default('generated')
                        ->required(),

                    Forms\Components\Toggle::make('with_claim_code')
                        ->label('Generate claim_code (optional)')
                        ->default(false),

                    Forms\Components\Select::make('kit_plan')
                        ->label('Default kit_plan (optional)')
                        ->options([
                            'registered' => 'registered',
                            'basic' => 'basic',
                            'solo' => 'solo',
                            'premium' => 'premium',
                            'enterprise' => 'enterprise',
                        ])
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    $batch = $this->record;

                    $qty = (int) $data['quantity'];
                    $status = $data['status'];
                    $withClaim = (bool) $data['with_claim_code'];
                    $kitPlan = $data['kit_plan'] ?? null;

                    if ($qty > $batch->remaining) {
                        Notification::make()
                            ->title('Insufficient QTY')
                            ->body("Unable to create {$qty} assets for batch {$batch->code}.")
                            ->danger()
                            ->send();
                        return false;
                    }

                    DB::transaction(function () use ($batch, $qty, $status, $withClaim, $kitPlan) {
                        $rows = [];

                        // Chunk inserts for speed
                        for ($i = 0; $i < $qty; $i++) {
                            $rows[] = [
                                'public_token' => (string) Str::uuid(),
                                'claim_code' => $withClaim ? strtoupper(Str::random(10)) : null,
                                'type' => $batch->asset_type,
                                'status' => $status,
                                'kit_plan' => $kitPlan,
                                'batch_id' => $batch->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $batch->generated++;
                            $batch->remaining--;

                            if (count($rows) >= 1000) {
                                SafeAsset::insert($rows);
                                $rows = [];
                            }
                        }
                        $batch->save();

                        if (!empty($rows)) {
                            SafeAsset::insert($rows);
                        }
                    });

                    Notification::make()
                        ->title('QR assets generated')
                        ->body("Created {$qty} assets for batch {$batch->code}.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['updated_at']);
                }),

            Actions\Action::make('printBatch')
                ->icon('heroicon-o-printer')
                ->url(fn ($record) => route('print.batch', $record))
                ->disabled(fn ($record) => $record->generated === 0)
                ->openUrlInNewTab(),

            Actions\Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $batch = $this->record;

                    $assets = $batch->assets()
                        ->select(['public_token', 'claim_code', 'type', 'status', 'kit_plan', 'created_at'])
                        ->orderBy('created_at')
                        ->get();

                    $appUrl = rtrim(config('app.url'), '/');

                    $filename = 'batch-' . $batch->code . '-qr-assets.csv';

                    return response()->streamDownload(function () use ($assets, $appUrl) {
                        $out = fopen('php://output', 'w');

                        fputcsv($out, [
                            'public_token',
                            'qr_url',
                            'claim_code',
                            'type',
                            'status',
                            'kit_plan',
                            'created_at',
                        ]);

                        foreach ($assets as $a) {
                            fputcsv($out, [
                                $a->public_token,
                                $appUrl . '/c/' . $a->public_token,
                                $a->claim_code,
                                $a->type,
                                $a->status,
                                $a->kit_plan,
                                optional($a->created_at)->toDateTimeString(),
                            ]);
                        }

                        fclose($out);
                    }, $filename, [
                        'Content-Type' => 'text/csv',
                    ]);
                }),
        ];
    }
}