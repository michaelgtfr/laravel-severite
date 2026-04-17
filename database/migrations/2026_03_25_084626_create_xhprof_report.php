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
        Schema::create('xhprof_report', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('title');
            $table->text('report');
            $table->string('tag')->nullable(true);
            $table->integer('wall_time');
            $table->integer('memory_usage');
            $table->integer('peak_memory_usage');
            $table->integer('central_processing_unit');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xhprof_report');
    }
};
