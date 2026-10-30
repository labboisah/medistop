<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Category;
use App\Models\RevenueSharingRule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_financial_shares_are_aggregated_for_the_current_period_only(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 30)->setTime(12, 0));

        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Imaging']);
        RevenueSharingRule::create([
            'category_id' => $category->id,
            'staff_percent' => 10,
            'annex_percent' => 50,
            'radiographer_percent' => 20,
            'radiologist_percent' => 20,
        ]);
        $service = Service::create(['category_id' => $category->id, 'name' => 'X-Ray', 'price' => 100]);

        $currentBill = Bill::create([
            'bill_no' => 'AUDIT-CURRENT',
            'user_id' => $user->id,
            'total_amount' => 100,
            'discount_amount' => 10,
            'final_amount' => 90,
            'total_paid' => 90,
            'balance' => 0,
            'payment_status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        BillItem::create(['bill_id' => $currentBill->id, 'service_id' => $service->id, 'price' => 100]);

        $priorYearBill = Bill::create([
            'bill_no' => 'AUDIT-PRIOR-YEAR',
            'user_id' => $user->id,
            'total_amount' => 200,
            'discount_amount' => 0,
            'final_amount' => 200,
            'total_paid' => 200,
            'balance' => 0,
            'payment_status' => 'paid',
        ]);
        $priorYearBill->forceFill([
            'created_at' => now()->subYear(),
            'updated_at' => now()->subYear(),
        ])->save();
        BillItem::create(['bill_id' => $priorYearBill->id, 'service_id' => $service->id, 'price' => 200]);

        $summary = $user->finacialCalculation();

        $this->assertEquals(100, $summary['todayGross']);
        $this->assertEquals(10, $summary['todayDiscount']);
        $this->assertEquals(9, $summary['todayStaffShare']);
        $this->assertEquals(45, $summary['todayAnnexShare']);
        $this->assertEquals(18, $summary['todayRadiographerShare']);
        $this->assertEquals(18, $summary['todayRadiologistShare']);
        $this->assertEquals(100, $summary['monthGross']);
        $this->assertEquals(9, $summary['monthStaffShare']);
    }
}
