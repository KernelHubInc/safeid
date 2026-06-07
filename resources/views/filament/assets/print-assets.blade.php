<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; }

        table.sheet {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td.cell {
            width: 50%;
            height: 31mm;
            padding: 2mm;
            vertical-align: top;
        }

        .sticker {
            position: relative;
            width: 100%;
            height: 31mm;         /* IMPORTANT: fixed height */
            overflow: hidden;
            border-radius: 5px;
        }

        .bg {
            position: absolute;
            top: 0; left: 0;      /* Use top/left instead of inset */
            width: 100%;
            height: 100%;
        }

        /* QR inside the white box (tweak these 3 values only) */
        .qr {
            position: absolute;
            top: 1.7mm;
            right: 2.5mm;
            width: 27mm;
            height: 27mm;
        }

        .serial {
            position: absolute;
            top: 26mm;
            right: 8mm;
            font-size:10px;
        }

        .qr img { width: 100%; height: 100%; }
    </style>
</head>
<body>

@php
    $mode = $mode ?? 'batch';
@endphp

@if($mode === 'single')
    @php
        // Works for array or collection
        $asset = is_array($assets) ? ($assets[0] ?? null) : ($assets->first() ?? null);
    @endphp

    @if($asset)
        <table class="sheet">
            @for($r=0; $r<8; $r++)
                <tr>
                    @for($c=0; $c<2; $c++)
                        <td class="cell">
                            @if($r < 2 && $c < 2)
                                <div class="sticker">
                                    <img class="bg" src="{{ $bg_base64 }}" alt="Sticker">
                                    <div class="qr">
                                        <img src="data:image/png;base64,{{ $asset->qr_base64 }}" alt="QR">
                                    </div>
                                    <div class="serial">{{ str_pad($asset->id, 9, "0", STR_PAD_LEFT) }}</div>
                                </div>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endfor
        </table>
    @endif

@else
    @php
        $collection = is_array($assets) ? collect($assets) : $assets;
        $pages = $collection->chunk(4)->values(); // 4 unique assets per page
    @endphp

    @foreach($pages as $pageIndex => $pageAssets)
        @php
            $slots = array_pad($pageAssets->values()->all(), 4, null);
        @endphp

        <table class="sheet">
            @for($row=0; $row<8; $row++)
                <tr>
                    @for($col=0; $col<2; $col++)
                        @php
                            $slotIndex = intdiv($row, 2) + intdiv($col, 2);
                            $asset = $slots[$slotIndex];
                        @endphp

                        <td class="cell">
                            @if($asset)
                                <div class="sticker">
                                    <img class="bg" src="{{ $bg_base64 }}" alt="Sticker">
                                    <div class="qr">
                                        <img src="data:image/png;base64,{{ $asset->qr_base64 }}" alt="QR">
                                    </div>
                                    <div class="serial">{{ str_pad($asset->id, 9, "0", STR_PAD_LEFT) }}</div>
                                </div>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endfor
        </table>

        @if($pageIndex < $pages->count() - 1)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
@endif

</body>
</html>