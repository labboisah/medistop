<!-- SUMMARY CARDS -->
<div class="grid md:grid-cols-4 gap-6 mb-10">

    <div class="bg-white p-6 rounded-2xl shadow">
        <h3 class="text-sm text-gray-500">Revenue Today</h3>
        <p class="text-2xl font-bold text-primary mt-2">
            ₦{{ number_format(0, 2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <h3 class="text-sm text-gray-500">Staff Allocation</h3>
        <p class="text-2xl font-bold text-accent mt-2">
            ₦{{ number_format(0, 2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <h3 class="text-sm text-gray-500">Expenses Today</h3>
        <p class="text-2xl font-bold text-red-600 mt-2">
            ₦{{ number_format(0, 2) }}
        </p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow">
        <h3 class="text-sm text-gray-500">Net Balance</h3>
        <p class="text-2xl font-bold text-secondary mt-2">
            ₦{{ number_format(0, 2) }}
        </p>
    </div>

</div>

<!-- QUICK ACTIONS -->
<div class="grid md:grid-cols-4 gap-6 mb-12">

    <a href="#"
       class="bg-primary text-white p-6 rounded-2xl shadow hover:bg-secondary transition">
        📝 Record Service
    </a>

    <a href="#"
       class="bg-accent text-white p-6 rounded-2xl shadow hover:bg-green-600 transition">
        💸 Add Expense
    </a>

    <a href="#"
       class="bg-blue-700 text-white p-6 rounded-2xl shadow hover:bg-blue-800 transition">
        💳 Record Payment
    </a>

    <a href="#"
       class="bg-gray-800 text-white p-6 rounded-2xl shadow hover:bg-black transition">
        📄 Generate PDF Report
    </a>

</div>

<!-- RECENT ACTIVITIES -->
<div class="grid md:grid-cols-2 gap-8">

    <!-- Recent Services -->
    <div class="bg-white rounded-2xl shadow p-6">
        <h3 class="font-bold text-primary mb-4">Recent Services</h3>

        @forelse([] as $record)
            <div class="border-b py-3 text-sm flex justify-between">
                <span>{{ $record->service->name ?? '' }}</span>
                <span class="font-semibold text-primary">
                    ₦{{ number_format($record->amount, 2) }}
                </span>
            </div>
        @empty
            <p class="text-gray-500 text-sm">No service records yet.</p>
        @endforelse
    </div>

    <!-- Recent Expenses -->
    <div class="bg-white rounded-2xl shadow p-6">
        <h3 class="font-bold text-primary mb-4">Recent Expenses</h3>

        @forelse([] as $expense)
            <div class="border-b py-3 text-sm flex justify-between">
                <span>{{ $expense->description }}</span>
                <span class="font-semibold text-red-600">
                    ₦{{ number_format($expense->amount, 2) }}
                </span>
            </div>
        @empty
            <p class="text-gray-500 text-sm">No expenses recorded yet.</p>
        @endforelse
    </div>

</div>

