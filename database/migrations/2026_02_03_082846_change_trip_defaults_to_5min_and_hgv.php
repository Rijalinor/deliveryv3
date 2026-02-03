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
        Schema::table('trips', function (Blueprint $table) {
            $table->unsignedSmallInteger('service_minutes')->default(5)->change();
            $table->string('ors_profile')->default('driving-hgv')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->unsignedSmallInteger('service_minutes')->default(15)->change();
            $table->string('ors_profile')->default('driving-car')->change();
        });
    }
};
