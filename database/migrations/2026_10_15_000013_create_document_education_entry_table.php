<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_education_entry', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('education_entry_id')->constrained()->cascadeOnDelete();
            $table->primary(['document_id', 'education_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_education_entry');
    }
};
