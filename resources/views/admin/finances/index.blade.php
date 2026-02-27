@extends('layouts.app')

@section('page-title', 'Financial Summary')

@section('content')

<div class="grid md:grid-cols-4 gap-6 mb-10">

    <!-- Revenue -->
    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Revenue Today</p>
        <p class="text-2xl font-bold text-primary mt-2">
            ₦{{ number_format($report['todayRevenue'],2) }}
        </p>
    </div>

    <!-- Payments -->
    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Cash Collected Today</p>
        <p class="text-2xl font-bold text-accent mt-2">
            ₦{{ number_format($report['todayPayments'],2) }}
        </p>
    </div>

    <!-- Expenses -->
    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Expenses Today</p>
        <p class="text-2xl font-bold text-red-600 mt-2">
            ₦{{ number_format($report['todayExpenses'],2) }}
        </p>
    </div>

    <!-- Profit -->
    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Net Profit Today</p>
        <p class="text-2xl font-bold 
            {{ $report['todayProfit'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
            ₦{{ number_format($report['todayProfit'],2) }}
        </p>
    </div>

</div>

<div class="grid md:grid-cols-4 gap-6 mb-10">

    <!-- Outstanding -->
    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Outstanding Bills (Today)</p>
        <p class="text-2xl font-bold text-yellow-600 mt-2">
            ₦{{ number_format($report['todayOutstanding'],2) }}
        </p>
    </div>

    <!-- Monthly Revenue -->
    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Revenue This Month</p>
        <p class="text-2xl font-bold text-primary mt-2">
            ₦{{ number_format($report['monthRevenue'],2) }}
        </p>
    </div>

    <!-- Monthly Expenses -->
    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Expenses This Month</p>
        <p class="text-2xl font-bold text-red-600 mt-2">
            ₦{{ number_format($report['monthExpenses'],2) }}
        </p>
    </div>

    <!-- Monthly Profit -->
    <div class="bg-white p-6 rounded-2xl shadow">
        <p class="text-sm text-gray-500">Net Profit This Month</p>
        <p class="text-2xl font-bold 
            {{ $report['monthProfit'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
            ₦{{ number_format($report['monthProfit'],2) }}
        </p>
    </div>

</div>

<div class="bg-white rounded-2xl shadow mb-8 overflow-hidden">

    <button onclick="toggleSection('todaySection', 'todayIcon')"
        class="w-full flex justify-between items-center px-6 py-4 text-left">

        <h3 class="text-lg font-bold text-primary">
            View {{date('d-M-Y')}}'s Financial Summary in Chart
        </h3>

        <!-- Chevron -->
        <svg id="todayIcon"
             class="w-5 h-5 transition-transform duration-300"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div id="todaySection"
         class="hidden px-6 pb-6 transition-all duration-500 overflow-hidden">

        <canvas id="todayChart"></canvas>

    </div>
</div>

<div class="bg-white rounded-2xl shadow mb-8 overflow-hidden">

    <button onclick="toggleSection('monthSection', 'monthIcon')"
        class="w-full flex justify-between items-center px-6 py-4 text-left">

        <h3 class="text-lg font-bold text-primary">
            View {{date('M, Y')}}'s Financial Summary in Chart
        </h3>

        <!-- Chevron -->
        <svg id="monthIcon"
             class="w-5 h-5 transition-transform duration-300"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div id="monthSection"
        class="hidden px-6 pb-6 transition-all duration-500 overflow-hidden">

        <canvas id="monthChart"></canvas>

    </div>
</div>
@endsection

@section('scripts')
<script>
const todayChart = new Chart(document.getElementById('todayChart'), {
    type: 'bar',
    data: {
        labels: ['Gross Revenue','Discount','Staff Share','Annex Share','Expenses','Profit'],
        datasets: [{
            label: 'Today',
            data: [
                {{ $todayGross }},
                {{ $todayDiscount }},
                {{ $todayStaffShare }},
                {{ $todayAnnexShare }},
                {{ $todayExpenses }},
                {{ $todayProfit }}
            ],
            backgroundColor: [
                '#1E4E8C',
                '#F59E0B',
                '#16A34A',
                '#0F2D5C',
                '#DC2626',
                '#10B981'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        }
    }
});


const monthChart = new Chart(document.getElementById('monthChart'), {
    type: 'bar',
    data: {
        labels: ['Gross Revenue','Discount','Staff Share','Annex Share','Expenses','Profit'],
        datasets: [{
            label: 'This Month',
            data: [
                {{ $monthGross }},
                {{ $monthDiscount }},
                {{ $monthStaffShare }},
                {{ $monthAnnexShare }},
                {{ $monthExpenses }},
                {{ $monthProfit }}
            ],
            backgroundColor: [
                '#1E4E8C',
                '#F59E0B',
                '#16A34A',
                '#0F2D5C',
                '#DC2626',
                '#10B981'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        }
    }
});
</script>
<script>
function toggleSection(sectionId, iconId) {

    const section = document.getElementById(sectionId);
    const icon = document.getElementById(iconId);

    if (section.classList.contains('hidden')) {
        section.classList.remove('hidden');
        icon.classList.remove('rotate-180');
    } else {
        section.classList.add('hidden');
        icon.classList.add('rotate-180');
    }
}
</script>


@endsection