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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'mobile_number')) {
                $table->unsignedBigInteger('mobile_number')->nullable()->change();
            } else {
                $table->unsignedBigInteger('mobile_number')->nullable()->after('name'); // adjust position if needed
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert back to INT or remove column if necessary (optional)
            $table->dropColumn('mobile_number');
        });
    }
};
