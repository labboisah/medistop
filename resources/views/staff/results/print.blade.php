<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Result - {{ $result->bill->bill_no }}</title>
    <style>
        body {
            color: #111827;
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f3f4f6;
        }
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            padding: 16mm 18mm;
            box-sizing: border-box;
            overflow: hidden;
            position: relative;
        }
        .watermark {
            left: 50%;
            opacity: 0.06;
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 420px;
            z-index: 0;
        }
        .report-content {
            position: relative;
            z-index: 1;
        }
        .header {
            border-bottom: 3px solid #0F2D5C;
            padding-bottom: 12px;
            margin-bottom: 18px;
            text-align: center;
        }
        .brand {
            color: #0F2D5C;
            font-size: 25px;
            font-weight: 800;
            letter-spacing: 0;
        }
        .sub-brand {
            color: #16A34A;
            font-size: 18px;
            font-weight: 700;
            margin-top: 2px;
        }
        .address {
            font-size: 12px;
            margin-top: 4px;
        }
        .report-title {
            background: #0F2D5C;
            color: white;
            font-size: 15px;
            font-weight: 700;
            margin: 12px auto 0;
            padding: 6px 18px;
            text-transform: uppercase;
            width: fit-content;
        }
        .top-meta {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 82mm;
            align-items: start;
            gap: 8mm;
            font-size: 13px;
            margin-bottom: 10px;
        }
        .patient-details p {
            margin: 3px 0;
        }
        .patient-xray-images {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 2mm;
            align-items: start;
        }
        .patient-xray-images img {
            display: block;
            width: 100%;
            height: auto;
            max-height: 58mm;
            object-fit: contain;
        }
        .meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 24px;
            font-size: 13px;
            margin-bottom: 24px;
        }
        .section {
            margin-top: 10px;
        }
        .section h3 {
            color: #0F2D5C;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 5px;
            margin-bottom: 7px;
            font-size: 14px;
            text-transform: uppercase;
        }
        .content {
            white-space: pre-line;
            line-height: 1.45;
            font-size: 13px;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .image-grid img {
            max-width: 100%;
            max-height: 90mm;
            object-fit: contain;
        }
        .signature {
            margin-top: 22px;
            display: flex;
            justify-content: space-between;
            gap: 40px;
            font-size: 13px;
        }
        .line {
            border-top: 1px solid #111827;
            padding-top: 8px;
            width: 220px;
            text-align: center;
        }
        .actions {
            width: 210mm;
            margin: 20px auto 0;
            text-align: right;
        }
        .actions button,
        .actions a {
            background: #16A34A;
            border: 0;
            border-radius: 8px;
            color: white;
            display: inline-block;
            font-size: 14px;
            padding: 10px 16px;
            text-decoration: none;
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
            body {
                background: white;
            }
            .actions {
                display: none;
            }
            .sheet {
                margin: 0;
                width: auto;
                min-height: auto;
                padding: 0;
                overflow: visible;
            }
            .header {
                margin-bottom: 10px;
                padding-bottom: 8px;
            }
            .brand { font-size: 21px; }
            .sub-brand { font-size: 15px; }
            .address { font-size: 10px; }
            .report-title { margin-top: 8px; font-size: 13px; padding: 5px 14px; }
            .section { margin-top: 8px; }
            .signature { margin-top: 16px; }
        }
    </style>
</head>
<body>
    <div class="actions">
        @if(auth()->user()->role === 'user')
            <a href="{{ route('investigation-requests.show', $result->bill) }}">Back to Investigation</a>
        @else
            <a href="{{ route('staff.results.reports') }}">Back to Reports</a>
        @endif
        @if((auth()->user()->staff_type ?? null) === 'radiologist'
            && $result->billItem->service->is_xray
            && (int) $result->radiologist_id === (int) auth()->id())
            <a href="{{ route('staff.results.entry', $result->bill) }}#bill-item-{{ $result->bill_item_id }}">Edit Report</a>
        @endif
        <button onclick="window.print()">Print Result</button>
    </div>

    <main class="sheet">
        <img src="{{ asset('images/logo.png') }}" class="watermark" alt="">

        <div class="report-content">
            <div class="header">
                <div class="brand">MEDISTOP CLINICAL DIAGNOSTICS</div>
                <div class="sub-brand">ANNEX</div>
                <div class="address">SULTAN ABUBAKAR ROAD, MARINA</div>
                <div class="address">Contact: 07036354477, 08035677283</div>
                <div class="report-title">DIAGNOSTIC REPORT</div>
            </div>

            @php($isXray = (bool) $result->billItem->service->is_xray)
            <div class="top-meta">
                <div class="patient-details">
                    <p><strong>Patient:</strong> {{ $result->bill->patient_name ?? 'Walk-in' }}</p>
                    <p><strong>Gender:</strong> {{ $result->bill->gender ?? 'N/A' }}</p>
                    <p><strong>Age:</strong> {{ $result->bill->age ?? 'N/A' }}</p>
                    <p><strong>Category:</strong> {{ optional($result->billItem->service->category)->name ?? 'N/A' }}</p>
                    <p><strong>Investigation:</strong> {{ $result->billItem->service->name }}</p>
                </div>
                @if($isXray && $result->images->isNotEmpty())
                    <div class="patient-xray-images" aria-label="X-Ray image">
                        @foreach($result->images as $image)
                            @if(str_starts_with(strtolower($image->mime_type ?? ''), 'image/'))
                                <img src="{{ route('staff.results.images.view', $image) }}" alt="{{ $image->original_name }}">
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            @if($result->clinical_note)
                <section class="section">
                    <h3>Clinical Note: {{ $result->clinical_note }}</h3>
                </section>
            @endif

            <section class="section">
                <h3>Findings</h3>
                <div class="content">{{ $result->findings }}</div>
            </section>

            @if($result->impression)
                <section class="section">
                    <h3>Impression</h3>
                    <div class="content">{{ $result->impression }}</div>
                </section>
            @endif

            @if(! $isXray && $result->images->isNotEmpty())
                <section class="section">
                    <h3>Imaging Attachments</h3>
                    <div class="image-grid">
                        @foreach($result->images as $image)
                            @if(str_starts_with(strtolower($image->mime_type ?? ''), 'image/'))
                                <img src="{{ route('staff.results.images.view', $image) }}" alt="{{ $image->original_name }}">
                            @else
                                <p>{{ $image->original_name }} (available electronically)</p>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="signature">
                <div class="line">
                    
                    {{ optional($result->reporter)->name ?? 'N/A' }}<br>
                    {{optional($result->reporter)->designation ?? 'N/A'}}<br>
                    {{ optional($result->completed_at)->format('d M Y h:i A') }} 
                </div>
            </div>
        </div>
    </main>
</body>
</html>
