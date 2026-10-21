<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_certification', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certification_id')->constrained()->cascadeOnDelete();
            $table->primary(['document_id', 'certification_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_certification');
    }
};
