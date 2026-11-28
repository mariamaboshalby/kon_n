<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cvs')) {
            return;
        }

        if (! Schema::hasColumn('cvs', 'branch_id') && Schema::hasTable('branches')) {
            Schema::table('cvs', function (Blueprint $table): void {
                $table->foreignId('branch_id')->nullable()->default(1)->constrained('branches')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('cvs', 'job_posting_id')) {
            return;
        }

        Schema::table('cvs', function (Blueprint $table): void {
            if (Schema::hasColumn('cvs', 'branch_id')) {
                $table->foreignId('job_posting_id')->nullable()->after('branch_id')->constrained('job_postings')->nullOnDelete();

                return;
            }

            $table->foreignId('job_posting_id')->nullable()->constrained('job_postings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cvs') || ! Schema::hasColumn('cvs', 'job_posting_id')) {
            return;
        }

        Schema::table('cvs', function (Blueprint $table): void {
            $table->dropForeign(['job_posting_id']);
            $table->dropColumn('job_posting_id');
        });
    }
};
