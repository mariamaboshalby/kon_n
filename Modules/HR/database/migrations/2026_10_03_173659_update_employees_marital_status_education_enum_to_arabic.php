<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migrate marital_status and education ENUM columns from English values to
 * Arabic values, matching the model mutators in Employee.php.
 *
 * Pattern mirrors 2026_04_06_115057_update_employee_status_enum_values.php
 * which did the same for the `status` column.
 *
 * marital_status:
 *   single    → غير متزوج
 *   married   → متزوج
 *   divorced  → مطلق
 *   widowed   → أرمل
 *
 * education:
 *   high_school → دبلوم  (no Arabic mapping existed; closest equivalent)
 *   diploma     → دبلوم
 *   bachelor    → بكالوريوس
 *   master      → ماجستير
 *   doctorate   → دكتوراه
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── marital_status ────────────────────────────────────────────────────
        // 1. Expand the ENUM to hold both old and new values simultaneously.
        DB::statement("ALTER TABLE employees MODIFY COLUMN marital_status
            ENUM('single','married','divorced','widowed','غير متزوج','متزوج','مطلق','أرمل')
            NULL");

        // 2. Translate existing English rows to Arabic.
        DB::statement("UPDATE employees SET marital_status = 'غير متزوج' WHERE marital_status = 'single'");
        DB::statement("UPDATE employees SET marital_status = 'متزوج'     WHERE marital_status = 'married'");
        DB::statement("UPDATE employees SET marital_status = 'مطلق'      WHERE marital_status = 'divorced'");
        DB::statement("UPDATE employees SET marital_status = 'أرمل'      WHERE marital_status = 'widowed'");

        // 3. Lock the ENUM to Arabic-only values.
        DB::statement("ALTER TABLE employees MODIFY COLUMN marital_status
            ENUM('غير متزوج','متزوج','مطلق','أرمل')
            NULL");

        // ── education ─────────────────────────────────────────────────────────
        // 1. Expand the ENUM to hold both old and new values.
        DB::statement("ALTER TABLE employees MODIFY COLUMN education
            ENUM('high_school','diploma','bachelor','master','doctorate','دبلوم','بكالوريوس','ماجستير','دكتوراه')
            NULL");

        // 2. Translate existing English rows to Arabic.
        //    high_school has no direct Arabic mapping in the model; map to 'دبلوم'.
        DB::statement("UPDATE employees SET education = 'دبلوم'      WHERE education = 'high_school'");
        DB::statement("UPDATE employees SET education = 'دبلوم'      WHERE education = 'diploma'");
        DB::statement("UPDATE employees SET education = 'بكالوريوس'  WHERE education = 'bachelor'");
        DB::statement("UPDATE employees SET education = 'ماجستير'    WHERE education = 'master'");
        DB::statement("UPDATE employees SET education = 'دكتوراه'    WHERE education = 'doctorate'");

        // 3. Lock the ENUM to Arabic-only values.
        DB::statement("ALTER TABLE employees MODIFY COLUMN education
            ENUM('دبلوم','بكالوريوس','ماجستير','دكتوراه')
            NULL");
    }

    public function down(): void
    {
        // ── marital_status ────────────────────────────────────────────────────
        DB::statement("ALTER TABLE employees MODIFY COLUMN marital_status
            ENUM('غير متزوج','متزوج','مطلق','أرمل','single','married','divorced','widowed')
            NULL");

        DB::statement("UPDATE employees SET marital_status = 'single'   WHERE marital_status = 'غير متزوج'");
        DB::statement("UPDATE employees SET marital_status = 'married'  WHERE marital_status = 'متزوج'");
        DB::statement("UPDATE employees SET marital_status = 'divorced' WHERE marital_status = 'مطلق'");
        DB::statement("UPDATE employees SET marital_status = 'widowed'  WHERE marital_status = 'أرمل'");

        DB::statement("ALTER TABLE employees MODIFY COLUMN marital_status
            ENUM('single','married','divorced','widowed')
            NULL");

        // ── education ─────────────────────────────────────────────────────────
        DB::statement("ALTER TABLE employees MODIFY COLUMN education
            ENUM('دبلوم','بكالوريوس','ماجستير','دكتوراه','high_school','diploma','bachelor','master','doctorate')
            NULL");

        DB::statement("UPDATE employees SET education = 'diploma'   WHERE education = 'دبلوم'");
        DB::statement("UPDATE employees SET education = 'bachelor'  WHERE education = 'بكالوريوس'");
        DB::statement("UPDATE employees SET education = 'master'    WHERE education = 'ماجستير'");
        DB::statement("UPDATE employees SET education = 'doctorate' WHERE education = 'دكتوراه'");

        DB::statement("ALTER TABLE employees MODIFY COLUMN education
            ENUM('high_school','diploma','bachelor','master','doctorate')
            NULL");
    }
};
