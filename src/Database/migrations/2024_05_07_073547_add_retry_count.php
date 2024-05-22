<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add a retry count to prevent the race condition betweeen the delete job and a retry upload request
        Schema::table('storage_request_files', function (Blueprint $table) {
            $table->unsignedInteger('retry_count')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storage_request_files', function (Blueprint $table) {
            $table->dropColumn('retry_count');
        });
    }
};
