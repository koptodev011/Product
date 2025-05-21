<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Change mobile_number to string (VARCHAR)
            $table->string('mobile_number', 20)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert back to BIGINT if needed (optional)
            $table->unsignedBigInteger('mobile_number')->nullable()->change();
        });
    }
};
