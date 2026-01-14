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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('driver_id')->constrained('users');

            $table->date('start_date');
            $table->time('start_time');

            $table->decimal('start_lat', 10, 7);
            $table->decimal('start_lng', 10, 7);

            $table->string('status')->default('planned'); // planned/on_going/done/cancelled
            $table->timestamp('generated_at')->nullable();

            $table->unsignedInteger('total_distance_m')->nullable();
            $table->unsignedInteger('total_duration_s')->nullable();
            $table->longText('route_geojson')->nullable();
            $table->string('ors_profile')->nullable();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
