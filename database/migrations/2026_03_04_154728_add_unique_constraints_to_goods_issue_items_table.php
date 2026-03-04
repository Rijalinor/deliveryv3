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
        Schema::table('goods_issue_items', function (Blueprint $table) {
            $table->unique(['goods_issue_id', 'pfi_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_issue_items', function (Blueprint $table) {
            $table->dropUnique(['goods_issue_id', 'pfi_number']);
        });
    }
};
