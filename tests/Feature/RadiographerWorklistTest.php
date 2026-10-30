<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\BillResult;
use App\Models\Category;
use App\Models\RevenueSharingRule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RadiographerWorklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_radiographer_worklist_shows_paid_unassigned_and_own_completed_work(): void
    {
        $radiographer = User::factory()->create(['role' => 'staff', 'staff_type' => 'radiographer']);
        $otherRadiographer = User::factory()->create(['role' => 'staff', 'staff_type' => 'radiographer']);
        $radiologist = User::factory()->create(['role' => 'staff', 'staff_type' => 'radiologist']);
        $service = $this->service();

        $awaitingUpload = $this->billItem($radiographer, $service, 'BILL-UPLOAD', 'Paid patient');
        $unpaid = $this->billItem($radiographer, $service, 'BILL-UNPAID', 'Unpaid patient', false);
        $readyToPrint = $this->billItem($radiographer, $service, 'BILL-READY', 'Ready patient');
        $otherWork = $this->billItem($otherRadiographer, $service, 'BILL-OTHER', 'Other patient');

        $this->createResult($readyToPrint, $radiographer, $radiologist, now());
        $this->createResult($otherWork, $otherRadiographer, $radiologist, now());

        $this->actingAs($radiographer)
            ->get(route('staff.results.index'))
            ->assertOk()
            ->assertSee('Paid patient')
            ->assertSee('Ready patient')
            ->assertDontSee('Unpaid patient')
            ->assertDontSee('Other patient')
            ->assertSee('Awaiting imaging upload')
            ->assertSee('Report ready to print');

        $this->actingAs($radiographer)
            ->get(route('staff.results.index', ['status' => 'awaiting_upload']))
            ->assertOk()
            ->assertSee('Paid patient')
            ->assertDontSee('Ready patient');
    }

    private function service(): Service
    {
        $category = Category::create(['name' => 'Imaging']);
        RevenueSharingRule::create([
            'category_id' => $category->id,
            'staff_percent' => 10,
            'annex_percent' => 50,
            'radiographer_percent' => 20,
            'radiologist_percent' => 20,
        ]);

        return Service::create(['category_id' => $category->id, 'name' => 'Chest X-Ray', 'price' => 100]);
    }

    private function billItem(User $owner, Service $service, string $number, string $patient, bool $paid = true): BillItem
    {
        $bill = Bill::create([
            'bill_no' => $number,
            'patient_name' => $patient,
            'user_id' => $owner->id,
            'total_amount' => 100,
            'discount_amount' => 0,
            'final_amount' => 100,
            'total_paid' => $paid ? 100 : 0,
            'balance' => $paid ? 0 : 100,
            'payment_status' => $paid ? 'paid' : 'unpaid',
        ]);

        return BillItem::create(['bill_id' => $bill->id, 'service_id' => $service->id, 'price' => 100]);
    }

    private function createResult(BillItem $item, User $radiographer, User $radiologist, $reportedAt): BillResult
    {
        return BillResult::create([
            'bill_id' => $item->bill_id,
            'bill_item_id' => $item->id,
            'staff_id' => $radiographer->id,
            'performed_by' => $radiographer->id,
            'reported_by' => $radiologist->id,
            'radiologist_id' => $radiologist->id,
            'performed_at' => now(),
            'reported_at' => $reportedAt,
            'findings' => 'Test finding',
            'status' => 'completed',
        ]);
    }
}
