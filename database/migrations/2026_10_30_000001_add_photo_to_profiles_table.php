<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('photo_path')->nullable();
            $table->string('photo_mime', 50)->nullable();
            $table->unsignedTinyInteger('photo_focus_x')->default(50);
            $table->unsignedTinyInteger('photo_focus_y')->default(50);
            $table->unsignedSmallInteger('photo_zoom')->default(100);
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'photo_path',
                'photo_mime',
                'photo_focus_x',
                'photo_focus_y',
                'photo_zoom',
            ]);
        });
    }
};
