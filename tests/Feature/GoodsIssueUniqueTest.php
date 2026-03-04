<?php

namespace Tests\Feature;

use App\Imports\TripsImport;
use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GoodsIssueUniqueTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_skips_existing_gi_number(): void
    {
        // 1. Setup existing GI
        GoodsIssue::create([
            'gi_number' => 'GI-ALREADY-EXISTS',
            'date' => now()->toDateString(),
            'status' => 'open',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'Skipping import for GI: GI-ALREADY-EXISTS'));

        // 2. Prepare import data with same GI
        $rows = new Collection([
            [
                'gi' => 'GI-ALREADY-EXISTS',
                'pfi' => 'PFI-NEW',
                'outlet' => 'Toko Baru',
                'address' => 'Alamat Baru',
            ]
        ]);

        // 3. Run Import
        $import = new TripsImport();
        $import->collection($rows);

        // 4. Verification: No new items should be created for this GI
        $this->assertEquals(1, GoodsIssue::count());
        $this->assertEquals(0, GoodsIssueItem::count());
    }

    public function test_database_prevents_duplicate_pfi_in_same_gi(): void
    {
        $gi = GoodsIssue::create([
            'gi_number' => 'GI-001',
            'date' => now()->toDateString(),
            'status' => 'open',
        ]);

        GoodsIssueItem::create([
            'goods_issue_id' => $gi->id,
            'pfi_number' => 'PFI-DUP',
            'store_name' => 'Toko A',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // This should fail due to unique constraint ['goods_issue_id', 'pfi_number']
        GoodsIssueItem::create([
            'goods_issue_id' => $gi->id,
            'pfi_number' => 'PFI-DUP',
            'store_name' => 'Toko B',
        ]);
    }
}
