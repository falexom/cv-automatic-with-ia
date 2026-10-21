<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_work_experience', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_experience_id')->constrained()->cascadeOnDelete();
            $table->primary(['document_id', 'work_experience_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_work_experience');
    }
};
