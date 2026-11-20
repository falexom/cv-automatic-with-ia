<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certifications', function (Blueprint $table) {
            // Internal idempotency control for assisted/manual PDF imports.
            // It is not a professional field and is hidden from serialization.
            $table->string('import_confirmation_id', 36)->nullable();
            $table->unique('import_confirmation_id');
        });
    }

    public function down(): void
    {
        Schema::table('certifications', function (Blueprint $table) {
            $table->dropUnique(['import_confirmation_id']);
            $table->dropColumn('import_confirmation_id');
        });
    }
};
