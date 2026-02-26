<?php

namespace App\Console\Commands;

use App\Models\DriverLocation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PurgeDriverLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delivery:purge-locations {--days= : Menimpa nilai default dari config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghapus data lokasi driver (driver_locations) yang sudah kedaluwarsa untuk mencegah beban database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days') ?? config('delivery.location_retention_days', 30);
        $cutoffDate = Carbon::now()->subDays((int) $days);

        $this->info("Memulai purge data lokasi driver sebelum: {$cutoffDate->format('Y-m-d H:i:s')} (Umur > {$days} hari)");

        $count = DriverLocation::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0) {
            $this->info('Tidak ada data lama yang ditemukan. Clean!');

            return Command::SUCCESS;
        }

        // In cron, we should skip confirm. $this->option('no-interaction') handles this natively,
        // but to be safe, if we are running in non-interactive mode, or if we pass a special flag, skip confirmation.
        // We'll just execute it directly because it's a scheduled job.

        // Hapus dalam chunk menggunakan query builder agar cepat
        // Karena tidak ada soft delete di driver_locations, langsung delete()
        $deleted = DriverLocation::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Berhasil menghapus {$deleted} baris data lokasi.");

        return Command::SUCCESS;
    }
}
