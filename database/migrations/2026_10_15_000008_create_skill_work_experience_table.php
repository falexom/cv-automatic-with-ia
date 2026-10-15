<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_work_experience', function (Blueprint $table) {
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_experience_id')->constrained()->cascadeOnDelete();

            $table->primary(['skill_id', 'work_experience_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_work_experience');
    }
};
