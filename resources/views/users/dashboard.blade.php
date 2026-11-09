<!-- SUMMARY CARDS -->
<div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5">
    <div>
        <h2 class="font-bold text-amber-900">Staff commission claims</h2>
        <p class="mt-1 text-sm text-amber-800">Review requests assigned to you and record the amount actually settled.</p>
    </div>
    <a href="{{ route('commission-claims.index') }}" class="inline-flex items-center rounded-xl bg-amber-700 px-5 py-3 font-semibold text-white hover:bg-amber-800">Open claims to settle</a>
</div>

<div class="grid md:grid-cols-4 gap-6 mb-10">

    <div class="bg-white p-6 rounded-2xl shadow">
        <h3 class="text-sm text-gray-500">Revenue Today</h3>
        <p class="text-2xl font-bold text-primary mt-2">
            ₦{{ number_format($financialSummary['todayGross'], 2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <h3 class="text-sm text-gray-500">Staff Allocation</h3>
        <p class="text-2xl font-bold text-accent mt-2">
            ₦{{ number_format($financialSummary['todayStaffShare'], 2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <h3 class="text-sm text-gray-500">Radiologist Allocation</h3>
        <p class="text-2xl font-bold text-accent mt-2">
            ₦{{ number_format($financialSummary['todayRadiologistShare'], 2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <h3 class="text-sm text-gray-500">Radiographers Allocation</h3>
        <p class="text-2xl font-bold text-accent mt-2">
            ₦{{ number_format($financialSummary['todayRadiographerShare'], 2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <h3 class="text-sm text-gray-500">Refunds Today</h3>
        <p class="text-2xl font-bold text-red-600 mt-2">
            ₦{{ number_format($financialSummary['todayRefunds'], 2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <h3 class="text-sm text-gray-500">Expenses Today</h3>
        <p class="text-2xl font-bold text-red-600 mt-2">
            ₦{{ number_format($financialSummary['todayExpenses'], 2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <h3 class="text-sm text-gray-500">Expenses Discount</h3>
        <p class="text-2xl font-bold text-red-600 mt-2">
            ₦{{ number_format($financialSummary['todayDiscount'], 2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <h3 class="text-sm text-gray-500">Profit Today</h3>
        <p class="text-2xl font-bold text-secondary mt-2">
            ₦{{ number_format($financialSummary['todayProfit'], 2) }}
        </p>
    </div>

</div>

<!-- QUICK ACTIONS -->
<div class="grid md:grid-cols-4 gap-6 mb-12">

    <a href="{{route('bills.index')}}"
       class="bg-primary text-white p-6 rounded-2xl shadow hover:bg-secondary transition">
        📝 Bills
    </a>

    <a href="{{route('refunds.index')}}"
       class="bg-red-700 text-white p-6 rounded-2xl shadow hover:bg-red-800 transition">
        🔄 Refunds
    </a>

    <a href="{{route('expenses.index')}}"
       class="bg-accent text-white p-6 rounded-2xl shadow hover:bg-green-600 transition">
        💸 Expenses
    </a>

    <a href="{{route('payments.index')}}"
       class="bg-blue-700 text-white p-6 rounded-2xl shadow hover:bg-blue-800 transition">
        💳 Payment
    </a>

    <form method="POST" action="{{ route('reports.pdf') }}" id="pdfForm">
        @csrf
        <input type="hidden" name="chart_image" id="chartImageInput">
        <input type="hidden" name="from" value="{{ now()->startOfDay() }}">
        <input type="hidden" name="to" value="{{ now()->endOfDay() }}">

        <button type="submit"
            onclick="prepareChartImage()"
            class="bg-red-700 text-white p-6 rounded-2xl shadow hover:bg-blue-800 transition">
            Download Report in PDF
        </button>
    </form>

</div>

<!-- RECENT ACTIVITIES -->
<!-- RECENT BILLS -->
<div class="bg-white rounded-2xl shadow p-6">
    <h3 class="font-bold text-primary mb-4">Recent Bills</h3>

    @forelse([] as $bill)
        <div class="border-b py-3 text-sm flex justify-between">
            <span>
                {{ $bill->bill_no }}
                <br>
                <small class="text-gray-500">
                    {{ $bill->patient_name ?? 'Walk-in' }}
                </small>
            </span>

            <span class="font-semibold text-primary">
                ₦{{ number_format($bill->total_amount, 2) }}
            </span>
        </div>
    @empty
        <p class="text-gray-500 text-sm">No bills recorded yet.</p>
    @endforelse
</div>

