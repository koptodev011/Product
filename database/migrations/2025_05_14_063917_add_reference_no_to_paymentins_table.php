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
        Schema::table('paymentins', function (Blueprint $table) {
             $table->unsignedBigInteger('reference_no')->nullable()->after('party_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paymentins', function (Blueprint $table) {
              $table->dropColumn('reference_no');
        });
    }
};
