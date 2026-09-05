@extends('layouts.app')

@section('page-title', 'Staff Performance Audit')

@section('content')
<div class="bg-white p-6 rounded-2xl shadow mb-6">
    <div class="flex flex-wrap gap-3 justify-between mb-5">
        <div>
            <h2 class="text-xl font-bold text-primary">Daily Income Audit</h2>
            <p class="text-sm text-gray-500">Trace user activity by daily bill value and payments collected.</p>
        </div>

        <a href="{{ route('admin.staff-performance.download', request()->query()) }}"
           class="bg-accent text-white px-5 py-3 rounded-xl font-semibold">
            Download CSV
        </a>
    </div>

    <form method="GET" action="{{ route('admin.staff-performance.index') }}" class="grid md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-sm font-medium mb-2">From Date</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="w-full px-4 py-3 border rounded-xl">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">To Date</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="w-full px-4 py-3 border rounded-xl">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">User</label>
            <select name="user_id" class="w-full px-4 py-3 border rounded-xl">
                <option value="">All users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ (string) $userId === (string) $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ strtoupper($user->role ?? 'user') }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-3">
            <button class="bg-primary text-white px-6 py-3 rounded-xl">Generate</button>
            <a href="{{ route('admin.staff-performance.index') }}" class="bg-gray-200 text-primary px-6 py-3 rounded-xl">Reset</a>
        </div>

        @error('from')<p class="text-red-600 text-sm md:col-span-4">{{ $message }}</p>@enderror
        @error('to')<p class="text-red-600 text-sm md:col-span-4">{{ $message }}</p>@enderror
        @error('user_id')<p class="text-red-600 text-sm md:col-span-4">{{ $message }}</p>@enderror
    </form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">Bills</p>
        <p class="text-base xl:text-lg font-bold text-primary mt-2">{{ $totalBills }}</p>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">Gross</p>
        <p class="text-base xl:text-lg font-bold text-secondary mt-2">NGN {{ number_format($totalGross, 2) }}</p>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">Discount</p>
        <p class="text-base xl:text-lg font-bold text-orange-600 mt-2">NGN {{ number_format($totalDiscount, 2) }}</p>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">Net Revenue</p>
        <p class="text-base xl:text-lg font-bold text-accent mt-2">NGN {{ number_format($totalNet, 2) }}</p>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">Payments</p>
        <p class="text-base xl:text-lg font-bold text-indigo-600 mt-2">NGN {{ number_format($totalPayments, 2) }}</p>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">Salary Paid</p>
        <p class="text-base xl:text-lg font-bold text-yellow-600 mt-2">NGN {{ number_format($totalSalary, 2) }}</p>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">Expenses</p>
        <p class="text-base xl:text-lg font-bold text-red-600 mt-2">NGN {{ number_format($totalExpenses, 2) }}</p>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">Total Expenditure</p>
        <p class="text-base xl:text-lg font-bold text-red-700 mt-2">NGN {{ number_format($totalExpenditure, 2) }}</p>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">Profit After Expenditure</p>
        <p class="text-base xl:text-lg font-bold text-green-600 mt-2">NGN {{ number_format($totalProfit, 2) }}</p>
    </div>
</div>

<div class="bg-white p-6 rounded-2xl shadow mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-primary">Daily Trend</h3>
        <span class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} - {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</span>
    </div>

    <div class="h-80">
        <canvas id="staffPerformanceChart"></canvas>
    </div>
</div>

<div class="bg-white p-6 rounded-2xl shadow overflow-x-auto mb-6">
    <h3 class="text-lg font-bold text-primary mb-4">Summary By User</h3>

    <table class="w-full text-sm">
        <thead class="border-b bg-lightbg">
            <tr>
                <th class="py-3 px-4 text-left">User</th>
                <th class="py-3 px-4 text-left">Bills</th>
                <th class="py-3 px-4 text-right">Gross</th>
                <th class="py-3 px-4 text-right">Discount</th>
                <th class="py-3 px-4 text-right">Net Revenue</th>
                <th class="py-3 px-4 text-right">Payments</th>
                <th class="py-3 px-4 text-right">Radiologist Share</th>
                <th class="py-3 px-4 text-right">Radiographer Share</th>
                <th class="py-3 px-4 text-right">Salary</th>
                <th class="py-3 px-4 text-right">After Expenditure</th>
                <th class="py-3 px-4 text-right">Expenses</th>
                <th class="py-3 px-4 text-right">Total Expenditure</th>
                <th class="py-3 px-4 text-right">Profit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($userSummary as $row)
                <tr class="border-b">
                    <td class="py-3 px-4 font-semibold">{{ $row['user_name'] }}</td>
                    <td class="py-3 px-4">{{ $row['bill_count'] }}</td>
                    <td class="py-3 px-4 text-right">NGN {{ number_format($row['gross'], 2) }}</td>
                    <td class="py-3 px-4 text-right">NGN {{ number_format($row['discount'], 2) }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-accent">NGN {{ number_format($row['net'], 2) }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-indigo-600">NGN {{ number_format($row['payments'], 2) }}</td>
                    <td class="py-3 px-4 text-right text-purple-600">NGN {{ number_format($row['radiologist_share'] ?? 0, 2) }}</td>
                    <td class="py-3 px-4 text-right text-sky-600">NGN {{ number_format($row['radiographer_share'] ?? 0, 2) }}</td>
                    <td class="py-3 px-4 text-right text-yellow-600">NGN {{ number_format($row['salary_amount'] ?? 0, 2) }}</td>
                    <td class="py-3 px-4 text-right font-semibold">NGN {{ number_format($row['net_after_salary'] ?? $row['net'], 2) }}</td>
                    <td class="py-3 px-4 text-right text-red-600">NGN {{ number_format($row['expenses'], 2) }}</td>
                    <td class="py-3 px-4 text-right text-red-700">NGN {{ number_format($row['total_expenditure'] ?? $row['expenses'], 2) }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-green-600">NGN {{ number_format($row['profit'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="py-6 text-center text-gray-500">No user activity found for this duration.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="bg-white p-6 rounded-2xl shadow overflow-x-auto">
    <h3 class="text-lg font-bold text-primary mb-4">Daily User Breakdown</h3>

    <table class="w-full text-sm">
        <thead class="border-b bg-lightbg">
            <tr>
                <th class="py-3 px-4 text-left">Date</th>
                <th class="py-3 px-4 text-left">User</th>
                <th class="py-3 px-4 text-left">Bills</th>
                <th class="py-3 px-4 text-right">Gross</th>
                <th class="py-3 px-4 text-right">Discount</th>
                <th class="py-3 px-4 text-right">Net Revenue</th>
                <th class="py-3 px-4 text-right">Payments</th>
                <th class="py-3 px-4 text-right">Radiologist Share</th>
                <th class="py-3 px-4 text-right">Radiographer Share</th>
                <th class="py-3 px-4 text-right">Expenses</th>
                <th class="py-3 px-4 text-right">Total Expenditure</th>
                <th class="py-3 px-4 text-right">Profit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr class="border-b">
                    <td class="py-3 px-4">{{ $row['date_label'] }}</td>
                    <td class="py-3 px-4 font-semibold">{{ $row['user_name'] }}</td>
                    <td class="py-3 px-4">{{ $row['bill_count'] }}</td>
                    <td class="py-3 px-4 text-right">NGN {{ number_format($row['gross'], 2) }}</td>
                    <td class="py-3 px-4 text-right">NGN {{ number_format($row['discount'], 2) }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-accent">NGN {{ number_format($row['net'], 2) }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-indigo-600">NGN {{ number_format($row['payments'], 2) }}</td>
                    <td class="py-3 px-4 text-right text-purple-600">NGN {{ number_format($row['radiologist_share'] ?? 0, 2) }}</td>
                    <td class="py-3 px-4 text-right text-sky-600">NGN {{ number_format($row['radiographer_share'] ?? 0, 2) }}</td>
                    <td class="py-3 px-4 text-right text-red-600">NGN {{ number_format($row['expenses'], 2) }}</td>
                    <td class="py-3 px-4 text-right text-red-700">NGN {{ number_format($row['total_expenditure'] ?? $row['expenses'], 2) }}</td>
                    <td class="py-3 px-4 text-right font-semibold text-green-600">NGN {{ number_format($row['profit'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="py-6 text-center text-gray-500">No daily income found for this filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
function renderStaffPerformanceChart() {
    const canvas = document.getElementById('staffPerformanceChart');

    if (!canvas || !window.Chart) {
        return;
    }

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'Net Revenue',
                    data: @json($chartNetRevenue),
                    borderColor: '#16A34A',
                    backgroundColor: 'rgba(22, 163, 74, 0.12)',
                    tension: 0.35,
                    fill: true
                },
                {
                    label: 'Payments Collected',
                    data: @json($chartPayments),
                    borderColor: '#4F46E5',
                    backgroundColor: 'rgba(79, 70, 229, 0.10)',
                    tension: 0.35,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

if (window.Chart) {
    renderStaffPerformanceChart();
} else {
    window.addEventListener('app-assets-ready', renderStaffPerformanceChart, { once: true });
}
</script>
@endsection
