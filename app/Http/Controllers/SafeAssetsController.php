<?php

namespace App\Http\Controllers;

use App\Models\SafeAsset;
use Illuminate\Http\Response;
use App\Services\QrPngGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class SafeAssetsController extends Controller
{
    public function png(SafeAsset $asset): Response
    {   
        $url = env('APP_URL') . '/scan/' . $asset->public_token;

        $png = app(QrPngGenerator::class)->make($url);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function printAsset(SafeAsset $asset)
    {
        $assets = $this->hydrateAssetsWithQr([$asset]);

        return Pdf::loadView('filament.assets.print-assets', ['assets' => $assets, 'mode' => 'single', 'bg_base64' => $this->getBackground()])
            ->setPaper('a4')
            ->stream('qr-asset-' . ($asset->qr_code_id ?? $asset->id) . '.pdf');
    }

    public function printBatch(Batch $batch)
    {
        $assets = $batch->assets()->where('status', 'generated')->orderBy('created_at')->get();

        // 4 unique assets = 1 A4 page (16 stickers)
        $pages = $assets->chunk(4);

        $bgBase64 = $this->getBackground();

        Storage::disk('local')->makeDirectory('tmp');

        $zipName = 'batch-'.$batch->code.'-stickers.zip';
        $zipPath = storage_path('app/public/tmp/'.$zipName);

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $batchName = $batch->code;
        foreach ($pages as $i => $pageAssets) {
            // hydrate only 4 assets => small memory
            $pageAssetsWithQr = $this->hydrateAssetsWithQr($pageAssets->all());

            $pdf = Pdf::setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ])->loadView('filament.assets.print-assets', [
                'assets' => $pageAssetsWithQr,
                'mode' => 'batch',
                'bg_base64' => $bgBase64,
            ])->setPaper('a4');

            $pdfFilename = "page-{$batchName}-".str_pad((string)($i+1), 4, "0", STR_PAD_LEFT).".pdf";
            $pdfFullPath = storage_path('app/public/tmp/'.$pdfFilename);

            file_put_contents($pdfFullPath, $pdf->output());

            $zip->addFile($pdfFullPath, $pdfFilename);
        }

        $zip->close();

        // cleanup temp PDFs (optional)
        foreach (glob(storage_path('app/public/tmp/page-*.pdf')) as $file) {
            @unlink($file);
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    /**
     * Bulk print by selected asset IDs:
     * /print/assets?ids=ulid1,ulid2,ulid3
     */
    public function printAssets(Request $request)
    {
        $ids = collect(explode(',', (string) $request->string('ids')))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 422, 'No ids provided.');

        $assets = SafeAsset::query()
            ->whereIn('id', $ids)
            ->orderBy('created_at')
            ->get();

        abort_if($assets->isEmpty(), 404, 'No assets found.');

        $assets = $this->hydrateAssetsWithQr($assets->all());

        return Pdf::loadView('filament.assets.print-assets', ['assets' => $assets, 'mode' => 'batch', 'bg_base64' => $this->getBackground()])
            ->setPaper('a4')
            ->stream('qr-assets-bulk.pdf');
    }

    private function hydrateAssetsWithQr(array $assets): array
    {
        $appUrl = rtrim(config('app.url'), '/');
        $qr = app(QrPngGenerator::class);

        foreach ($assets as $asset) {
            $url = $appUrl . '/scan/' . $asset->public_token;

            // Your generator returns raw PNG bytes
            $png = $qr->make($url);

            $asset->scan_url = $url;
            $asset->qr_base64 = base64_encode($png);
        }

        return $assets;
    }

    private function zipFiles($files, $zipName)
    {
        $zipPath = storage_path("app/public/tmp/{$zipName}");

        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE);

        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    private function getBackground()
    {
        $bgPath = storage_path('app/public/public/sticker/emerion-card.png');

        return 'data:image/png;base64,' . base64_encode(file_get_contents($bgPath));
    }
}