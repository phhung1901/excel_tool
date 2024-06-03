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
        Schema::table('keywords', function (Blueprint $table) {
            $table->after('status', function (Blueprint $table) {
                $table->bigInteger('file_id');
                $table->jsonb('keyword_intent')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            //
        });
    }
};
