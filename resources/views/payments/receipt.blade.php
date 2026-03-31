<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $payment->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; position: relative; }
        .receipt-container { max-width: 820px; margin: auto; border: 1px solid #ccc; padding: 16px; position: relative; }
        .header { text-align: center; margin-bottom: 16px; }
        .section { margin-bottom: 14px; page-break-inside: avoid; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; }
        .table th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .bold { font-weight: 700; }
        .no-print { margin-bottom: 12px; }
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); opacity: 0.1; z-index: -1; pointer-events: none; }
        .footer-logo { text-align: center; margin-top: 20px; }
        .signature-section { margin-top: 40px; border-top: 1px solid #ccc; padding-top: 20px; }
        .signature-line { display: inline-block; width: 200px; border-bottom: 1px solid #000; margin-left: 20px; }
        .duplicate { page-break-before: always; }

        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            .receipt-container { border: none; width: 100%; }
            .section { page-break-inside: avoid; }
            .watermark { opacity: 0.05; }
        }
    </style>
</head>
<body>
<!-- Original Receipt -->
<div class="receipt-container">
    <div class="no-print">
        <button onclick="window.history.back()" style="margin-right: 10px; padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer;">← Back to Bills</button>
        <button onclick="window.print()" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">Print Receipt</button>
    </div>

    <!-- Watermark Logo -->
    <div class="watermark">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width: 300px; height: auto;">
    </div>

    <div class="header section">
        <div style="text-align: center; margin-bottom: 10px;">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width: 120px; height: auto;">
        </div>
        <h2>Payment Receipt</h2>
        <p>Bill No: {{ $payment->bill->bill_no }}</strong></p>
        <p>Issued: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="section">
        <h4>Bill Info</h4>
        <p>Patient: {{ $payment->bill->patient_name ?? 'N/A' }}</p>
        <p>Bill Created By: {{ optional($payment->bill->user)->name ?? 'Unknown' }}</p>
        <p>Payment Created By: {{ optional($payment->user)->name ?? auth()->user()->name }}</p>
        <p>Payment Date: {{ $payment->created_at->format('d M Y H:i') }}</p>
    </div>

    <div class="section">
        <h4>Payment Details</h4>
        <p>Amount Paid: ₦{{ number_format($payment->amount, 2) }}</p>
        <p>Payment Method: {{ $payment->payment_method ?? 'Cash' }}</p>
        @if($payment->note)
            <p>Note: {{ $payment->note }}</p>
        @endif
    </div>

    @php
        $items = $payment->bill->items ?? collect();
        $showDistribution = $items->where('revenueDistribution', '!=', null)->filter(function($item){
            return $item->revenueDistribution->radiologist_amount > 0 || $item->revenueDistribution->radiographer_amount > 0 || $item->revenueDistribution->staff_amount > 0 || $item->revenueDistribution->annex_amount > 0;
        })->isNotEmpty();
    @endphp

    @if($items->isNotEmpty())
        <div class="section">
            <h4>Services</h4>
            <table class="table">
                <thead>
                <tr>
                    <th>Service</th>
                    <th class="text-right">Price</th>
                    @if($showDistribution)<th class="text-right">Staff</th><th class="text-right">Annex</th><th class="text-right">Radiologist</th><th class="text-right">Radiographer</th>@endif
                </tr>
                </thead>
                <tbody>

                @foreach($items as $item)
                    @php
                        $d = $item->revenueDistribution;
                    @endphp
                    <tr>
                        <td>{{ optional($item->service)->name ?? 'Unknown service' }}</td>
                        <td class="text-right">₦{{ number_format($item->price, 2) }}</td>
                        @if($showDistribution)
                            <td class="text-right">{{ $d && $d->staff_amount > 0 ? '₦'.number_format($d->staff_amount,2) : '-' }}</td>
                            <td class="text-right">{{ $d && $d->annex_amount > 0 ? '₦'.number_format($d->annex_amount,2) : '-' }}</td>
                            <td class="text-right">{{ $d && $d->radiologist_amount > 0 ? '₦'.number_format($d->radiologist_amount,2) : '-' }}</td>
                            <td class="text-right">{{ $d && $d->radiographer_amount > 0 ? '₦'.number_format($d->radiographer_amount,2) : '-' }}</td>
                        @endif
                    </tr>
                @endforeach

                </tbody>
            </table>
        </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div style="text-align: right;">
            <p>Authorized Signature:</p>
            <div class="signature-line" style="width: 250px;"></div>
        </div>
    </div>

    <div class="section" style="text-align: center; margin-top: 20px;">
        <p>*** This receipt is system generated and validated with signature ***</p>
    </div>
</div>

<!-- Duplicate Receipt -->
<div class="receipt-container duplicate">
    <div class="header section">
        <div style="text-align: center; margin-bottom: 10px;">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width: 120px; height: auto;">
        </div>
        <h2>Payment Receipt (Duplicate)</h2>
        <p>Receipt ID: <strong>#{{ $payment->id }}</strong></p>
        <p>Issued: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <!-- Watermark Logo -->
    <div class="watermark">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width: 300px; height: auto;">
    </div>

    <div class="section">
        <h4>Bill Info</h4>
        <p>Bill No: {{ $payment->bill->bill_no }}</p>
        <p>Patient: {{ $payment->bill->patient_name ?? 'N/A' }}</p>
        <p>Bill Created By: {{ optional($payment->bill->user)->name ?? 'Unknown' }}</p>
        <p>Payment Created By: {{ optional($payment->user)->name ?? auth()->user()->name }}</p>
        <p>Payment Date: {{ $payment->created_at->format('d M Y H:i') }}</p>
    </div>

    <div class="section">
        <h4>Payment Details</h4>
        <p>Amount Paid: ₦{{ number_format($payment->amount, 2) }}</p>
        <p>Payment Method: {{ $payment->payment_method ?? 'Cash' }}</p>
        @if($payment->note)
            <p>Note: {{ $payment->note }}</p>
        @endif
    </div>

    @if($items->isNotEmpty())
        <div class="section">
            <h4>Services</h4>
            <table class="table">
                <thead>
                <tr>
                    <th>Service</th>
                    <th class="text-right">Price</th>
                    @if($showDistribution)<th class="text-right">Staff</th><th class="text-right">Annex</th><th class="text-right">Radiologist</th><th class="text-right">Radiographer</th>@endif
                </tr>
                </thead>
                <tbody>

                @foreach($items as $item)
                    @php
                        $d = $item->revenueDistribution;
                    @endphp
                    <tr>
                        <td>{{ optional($item->service)->name ?? 'Unknown service' }}</td>
                        <td class="text-right">₦{{ number_format($item->price, 2) }}</td>
                        @if($showDistribution)
                            <td class="text-right">{{ $d && $d->staff_amount > 0 ? '₦'.number_format($d->staff_amount,2) : '-' }}</td>
                            <td class="text-right">{{ $d && $d->annex_amount > 0 ? '₦'.number_format($d->annex_amount,2) : '-' }}</td>
                            <td class="text-right">{{ $d && $d->radiologist_amount > 0 ? '₦'.number_format($d->radiologist_amount,2) : '-' }}</td>
                            <td class="text-right">{{ $d && $d->radiographer_amount > 0 ? '₦'.number_format($d->radiographer_amount,2) : '-' }}</td>
                        @endif
                    </tr>
                @endforeach

                </tbody>
            </table>
        </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div style="text-align: right;">
            <p>Authorized Signature:</p>
            <div class="signature-line" style="width: 250px;"></div>
        </div>
    </div>

    <div class="section" style="text-align: center; margin-top: 20px;">
        <p>*** This receipt is system generated and valid without signature ***</p>
    </div>
</div>
</body>
</html>