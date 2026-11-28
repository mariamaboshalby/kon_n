<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftsTable extends Migration {

	public function up()
	{
		Schema::create('shifts', function(Blueprint $table) {
            $table->id();
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('shift_type', array('morning', 'evening', 'night'));
            $table->text('notes')->nullable();
            $table->json('days');
            $table->timestamps();
            $table->string('name');
            $table->time('beginning_check_in')->nullable();
            $table->time('ending_check_in')->nullable();
            $table->time('beginning_check_out')->nullable();
            $table->time('ending_check_out')->nullable();
            $table->integer('allowed_late_minutes')->nullable()->default(0);
            $table->integer('allowed_early_leave_minutes')->nullable()->default(0);
		});
	}

	public function down()
	{
		Schema::drop('shifts');
	}
}
