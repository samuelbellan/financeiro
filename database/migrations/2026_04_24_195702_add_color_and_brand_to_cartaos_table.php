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
        Schema::table('cartoes', function (Blueprint $table) {
            $table->string('cor')->default('#6366f1')->after('nome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cartoes', function (Blueprint $table) {
            $table->dropColumn(['cor']);
        });
    }
};
