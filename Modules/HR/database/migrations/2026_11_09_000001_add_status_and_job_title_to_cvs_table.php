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

        Schema::table('cvs', function (Blueprint $table): void {
            if (! Schema::hasColumn('cvs', 'status')) {
                $table->enum('status', ['pending', 'reviewed', 'accepted', 'rejected', 'on_hold'])
                      ->default('pending')
                      ->after('job_posting_id');
            }

            if (! Schema::hasColumn('cvs', 'job_title')) {
                $table->string('job_title')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cvs')) {
            return;
        }

        Schema::table('cvs', function (Blueprint $table): void {
            $columns = [];
            if (Schema::hasColumn('cvs', 'job_title')) {
                $columns[] = 'job_title';
            }
            if (Schema::hasColumn('cvs', 'status')) {
                $columns[] = 'status';
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
