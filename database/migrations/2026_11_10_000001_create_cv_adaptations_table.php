<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_adaptations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_profile_id')->constrained('cv_profiles')->cascadeOnDelete();
            $table->uuid('confirmation_id')->unique();
            $table->string('operation', 20);
            $table->string('job_title', 255);
            $table->string('company', 255)->nullable();
            $table->json('context');
            $table->timestamp('analyzed_at');
            $table->timestamp('confirmed_at');
            $table->timestamps();

            $table->index(['cv_profile_id', 'analyzed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_adaptations');
    }
};
