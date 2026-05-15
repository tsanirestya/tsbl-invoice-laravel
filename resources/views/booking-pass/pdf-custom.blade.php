<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { margin: 0; }
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 0; padding: 0; overflow: hidden; }
        .bp-canvas {
            position: relative;
            width: 210mm;
            height: 297mm;
            overflow: hidden;
        }
        .bp-bg {
            position: absolute;
            top: 0; left: 0;
            width: 210mm;
            height: 297mm;
        }
        .bp-field {
            position: absolute;
            box-sizing: border-box;
            word-break: break-word;
            line-height: 1.35;
        }
        .bp-field .field-label {
            display: block;
            font-size: 7pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: normal;
            margin-bottom: 1px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead tr { background: #0f1729; color: #fff; }
        thead th { padding: 4px 6px; font-size: 8pt; text-align: left; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody td { padding: 3px 6px; font-size: 9pt; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tfoot tr { background: #f1f5f9; font-weight: bold; }
        tfoot td { padding: 4px 6px; font-size: 9pt; }
    </style>
</head>
<body>
<div class="bp-canvas">

    {{-- Background image --}}
    @if($template && $template->template_file)
        @php
            $bgPath = public_path('storage/' . $template->template_file);
            $bgExt  = pathinfo($bgPath, PATHINFO_EXTENSION);
        @endphp
        @if(file_exists($bgPath))
            @php $bgB64 = base64_encode(file_get_contents($bgPath)); @endphp
            <img class="bp-bg" src="data:image/{{ $bgExt }};base64,{{ $bgB64 }}" alt="">
        @endif
    @endif

    {{-- Render each field at its saved position --}}
    @php
        use App\Services\BarcodeRenderer;
        $allFields = array_merge($fieldMap['fields'] ?? [], $fieldMap['custom_fields'] ?? []);
    @endphp

    @foreach($allFields as $field)
        @if(($field['visible'] ?? true) === false)
            @continue
        @endif
        @php
            $key            = $field['key'];
            $rawValue       = $fieldValues[$key] ?? '';
            $xPct           = $field['x_pct'] ?? 0;
            $yPct           = $field['y_pct'] ?? 0;
            $wPct           = $field['width_pct'] ?? 30;
            $fs             = $field['font_size'] ?? 12;
            $fw             = $field['font_weight'] ?? 'normal';
            $color          = $field['color'] ?? '#000000';
            $align          = $field['align'] ?? 'left';
            $showLabel      = ($field['show_label'] ?? true) !== false;
            $labelFs        = $field['label_font_size'] ?? 7;
            $labelColor     = $field['label_color'] ?? '#64748b';
            $outputType     = $field['output_type'] ?? 'text';

            $leftMm  = $xPct * 2.1;
            $topMm   = $yPct * 2.97;
            $widthMm = $wPct * 2.1;

            // Determine rendered content based on output_type
            $isHtml = false;
            if ($outputType === 'qr') {
                // If service already pre-rendered the QR as a base64 img tag, use it directly.
                // Calling strip_tags() on it would yield "" → qrserver gets empty data → returns
                // HTML error page → base64(html) is invalid PNG → dompdf shows broken X.
                if (str_contains($rawValue, '<img') && str_contains($rawValue, 'base64')) {
                    $displayValue = $rawValue;
                } else {
                    $plainText = trim(strip_tags($rawValue)) ?: $rawValue;
                    $url       = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($plainText);

                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    $imgData = curl_exec($ch);
                    $err     = curl_error($ch);
                    curl_close($ch);

                    // Validate response is a PNG (magic bytes \x89PNG)
                    if ($imgData && !$err && str_starts_with($imgData, "\x89PNG")) {
                        $displayValue = '<img src="data:image/png;base64,' . base64_encode($imgData) . '" style="width:100%; height:auto; display:block; margin:0 auto;" />';
                    } else {
                        $displayValue = '<div style="font-size:6pt; color:red; border:1px solid red; padding:2px;">'
                            . 'QR Fail: ' . ($err ?: ($imgData ? 'Invalid image data' : 'No data')) . '<br>'
                            . 'Data: ' . e($plainText)
                            . '</div>';
                    }
                }
                $isHtml = true;
            } elseif ($outputType === 'barcode') {
                $plainText    = strip_tags($rawValue);
                $displayValue = BarcodeRenderer::code39($plainText, 2, 55, true, true);
                $isHtml = true;
            } elseif (in_array($key, ['items_table', 'qr_code', 'logo'])) {
                $displayValue = $rawValue;
                $isHtml       = true;
            } else {
                $displayValue = $rawValue;
            }
        @endphp
        <div class="bp-field" style="
            left: {{ $leftMm }}mm;
            top: {{ $topMm }}mm;
            width: {{ $widthMm }}mm;
            font-size: {{ $fs }}pt;
            font-weight: {{ $fw }};
            color: {{ $color }};
            text-align: {{ $align }};
        ">
            @if($showLabel)
                <span class="field-label" style="font-size:{{ $labelFs }}pt;color:{{ $labelColor }};">{{ $field['label'] ?? $key }}</span>
            @endif
            @if($isHtml)
                {!! $displayValue !!}
            @else
                {{ $displayValue }}
            @endif
        </div>
    @endforeach

</div>
</body>
</html>
