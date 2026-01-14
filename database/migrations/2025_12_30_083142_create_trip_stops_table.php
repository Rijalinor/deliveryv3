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
        Schema::create('trip_stops', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
    
            $table->unsignedInteger('sequence')->nullable();
    
            // eta_at & close_at nanti dipakai untuk hitung & validasi
            $table->dateTime('eta_at')->nullable();
            $table->dateTime('close_at')->nullable();
    
            $table->boolean('is_late')->default(false);
            $table->unsignedInteger('late_minutes')->nullable();
    
            $table->string('status')->default('pending'); // pending/arrived/done/skipped
            $table->text('skip_reason')->nullable();
    
            $table->dateTime('arrived_at')->nullable();
            $table->dateTime('done_at')->nullable();
            $table->dateTime('skipped_at')->nullable();
    
            $table->softDeletes();
            $table->timestamps();
    
            // toko tidak boleh dobel di trip yang sama
            $table->unique(['trip_id', 'store_id']);
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_stops');
    }
};
