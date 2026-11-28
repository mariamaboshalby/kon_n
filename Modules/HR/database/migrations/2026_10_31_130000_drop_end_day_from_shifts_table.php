<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropEndDayFromShiftsTable extends Migration {

	public function up()
	{
		// The end_day feature was reverted; drop the column only on tenants
		// where the original migration had already run.
		if (Schema::hasColumn('shifts', 'end_day')) {
			Schema::table('shifts', function (Blueprint $table) {
				$table->dropColumn('end_day');
			});
		}
	}

	public function down()
	{
		if (! Schema::hasColumn('shifts', 'end_day')) {
			Schema::table('shifts', function (Blueprint $table) {
				$table->string('end_day', 10)->nullable()->after('end_time');
			});
		}
	}
}
