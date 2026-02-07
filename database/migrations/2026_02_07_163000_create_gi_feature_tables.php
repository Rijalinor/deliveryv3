<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Goods Issues (Header)
        Schema::create('goods_issues', function (Blueprint $table) {
            $table->id();
            $table->string('gi_number')->unique(); // e.g. GI123
            $table->date('date')->nullable();
            $table->string('status')->default('open')->index(); // open, assigned
            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Goods Issue Items (Detail)
        Schema::create('goods_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_issue_id')->constrained()->cascadeOnDelete();
            $table->string('pfi_number')->nullable();
            $table->string('store_name')->nullable(); // Raw name from excel
            $table->text('address')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            
            // Optional: Link to Store ID if resolved matched
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            
            $table->timestamps();
        });

        // 3. Trip Invoices (PFI per Stop)
        Schema::create('trip_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_stop_id')->constrained('trip_stops')->cascadeOnDelete();
            $table->string('pfi_number');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });

        // 4. Add gi_number to Trips
        Schema::table('trips', function (Blueprint $table) {
            $table->string('gi_number')->nullable()->after('driver_id'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('gi_number');
        });
        Schema::dropIfExists('trip_invoices');
        Schema::dropIfExists('goods_issue_items');
        Schema::dropIfExists('goods_issues');
    }
};
