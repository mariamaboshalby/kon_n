<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('applicants') || Schema::hasColumn('applicants', 'job_posting_id')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table): void {
            $table->foreignId('job_posting_id')
                  ->nullable()
                  ->after('name')
                  ->constrained('job_postings')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('applicants') || ! Schema::hasColumn('applicants', 'job_posting_id')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table): void {
            $table->dropForeign(['job_posting_id']);
            $table->dropColumn('job_posting_id');
        });
    }
};
