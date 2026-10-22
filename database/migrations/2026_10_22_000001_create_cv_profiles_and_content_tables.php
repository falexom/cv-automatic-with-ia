<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('normalized_name', 100)->unique();
            $table->string('professional_title', 255)->nullable();
            $table->text('summary')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cv_profile_work_experience', function (Blueprint $table) {
            $table->foreignId('cv_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_experience_id')->constrained()->cascadeOnDelete();
            $table->string('relevance', 10)->default('media');

            $table->primary(['cv_profile_id', 'work_experience_id']);
        });

        Schema::create('cv_profile_education_entry', function (Blueprint $table) {
            $table->foreignId('cv_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('education_entry_id')->constrained()->cascadeOnDelete();
            $table->string('relevance', 10)->default('media');

            $table->primary(['cv_profile_id', 'education_entry_id']);
        });

        Schema::create('cv_profile_certification', function (Blueprint $table) {
            $table->foreignId('cv_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certification_id')->constrained()->cascadeOnDelete();
            $table->string('relevance', 10)->default('media');

            $table->primary(['cv_profile_id', 'certification_id']);
        });

        Schema::create('cv_profile_skill', function (Blueprint $table) {
            $table->foreignId('cv_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('relevance', 10)->default('media');

            $table->primary(['cv_profile_id', 'skill_id']);
        });

        Schema::create('cv_profile_project', function (Blueprint $table) {
            $table->foreignId('cv_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('relevance', 10)->default('media');

            $table->primary(['cv_profile_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_profile_project');
        Schema::dropIfExists('cv_profile_skill');
        Schema::dropIfExists('cv_profile_certification');
        Schema::dropIfExists('cv_profile_education_entry');
        Schema::dropIfExists('cv_profile_work_experience');
        Schema::dropIfExists('cv_profiles');
    }
};
