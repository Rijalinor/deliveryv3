<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class TripsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $grouped = $rows->groupBy(function ($item) {
            return $item['gi'] ?? $item['gi_no'] ?? $item['goods_issue'] ?? null;
        });

        foreach ($grouped as $gi => $items) {
            if (empty($gi)) continue;

            DB::transaction(function () use ($gi, $items) {
                // Date logic
                $firstItem = $items->first();
                $dateVal = $firstItem['date'] ?? null;
                $date = now();
                if ($dateVal) {
                    try {
                        if (is_numeric($dateVal)) {
                            $date = Date::excelToDateTimeObject($dateVal);
                        } else {
                            $date = Carbon::parse($dateVal);
                        }
                    } catch (\Throwable $e) {}
                }

                $goodsIssue = GoodsIssue::firstOrCreate(
                    ['gi_number' => $gi],
                    ['date' => $date->format('Y-m-d'), 'status' => 'open']
                );

                foreach ($items as $item) {
                    $outletName = trim($item['outlet'] ?? $item['outlet_name'] ?? $item['customer'] ?? 'Unknown');
                    $pfi = $item['pfi'] ?? $item['pfi_no'] ?? $item['invoice'] ?? 'N/A';
                    $store = Store::where('name', $outletName)->first();

                    GoodsIssueItem::create([
                        'goods_issue_id' => $goodsIssue->id,
                        'pfi_number' => $pfi,
                        'store_name' => $outletName,
                        'address' => $item['address'] ?? null,
                        'store_id' => $store?->id,
                        'amount' => 0 // Add logic if amount is in excel
                    ]);
                }
            });
        }
    }
}
