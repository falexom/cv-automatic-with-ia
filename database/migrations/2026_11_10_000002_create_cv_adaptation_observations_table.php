<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_adaptation_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_adaptation_id')->constrained('cv_adaptations')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('reference', 100);
            $table->string('status', 20)->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['cv_adaptation_id', 'type', 'reference']);
            $table->index(['cv_adaptation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_adaptation_observations');
    }
};
