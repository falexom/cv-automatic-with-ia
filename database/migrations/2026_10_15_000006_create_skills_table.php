<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('normalized_name', 100);
            $table->string('category', 20);
            $table->timestamps();

            $table->unique(['profile_id', 'normalized_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
