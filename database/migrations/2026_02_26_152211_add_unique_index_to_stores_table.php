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
        Schema::table('stores', function (Blueprint $table) {
            // Change text to string so it can be indexed easily on MySQL
            $table->string('address', 255)->nullable()->change();

            $table->index('name');
            $table->index('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['address']);
            $table->text('address')->nullable()->change();
        });
    }
};
