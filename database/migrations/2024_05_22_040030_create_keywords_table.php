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
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('slug')->index();
            $table->string('source')->index();
            $table->string('field')->index();
            $table->jsonb('raw')->nullable()->comment('KD, Volume, ...');
            $table->jsonb('search_results')->nullable()->comment('top 10 SERP');
            $table->string('pos')->nullable()->index()->comment('POS tagging string');
            $table->tinyInteger('status')->default(0)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};
