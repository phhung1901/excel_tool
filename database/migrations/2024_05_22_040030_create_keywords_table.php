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
            $table->bigInteger('file_id')->index();
            $table->string('keyword');
            $table->string('slug');
            $table->jsonb('raw')->nullable();
            $table->string('source')->nullable();
            $table->string('field')->index()->nullable();
            $table->tinyInteger('status')->index()->default(0);
            $table->string('language')->index()->nullable();
            $table->unsignedBigInteger('duplicated_with')->nullable();
            $table->string('country',5)->nullable()->index();

            $table->jsonb('keyword_intent')->nullable();
            $table->string('pos')->nullable()->comment('POS tagging string')->index();

            $table->smallInteger('task_search_status')->default(0)->index();
            $table->timestamp('task_search_last')->nullable()->index();
            $table->tinyInteger('task_filter')->default(0)->nullable()->index();

            $table->smallInteger('task_pos_status')->default(0)->index();
            $table->timestamp('task_pos_last')->nullable()->index();
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
