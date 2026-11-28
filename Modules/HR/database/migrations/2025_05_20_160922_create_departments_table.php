<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateDepartmentsTable extends Migration {

	public function up()
	{
		Schema::create('departments', function(Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('director_id')->nullable();
            $table->unsignedBigInteger('deputy_director_id')->nullable();
            // self-referential FK is safe here
            $table->foreign('parent_id')->references('id')->on('departments')->nullOnDelete();
            // FKs to employees added in 2025_05_20_160923 (employees table created after departments alphabetically)
            $table->decimal('max_leave_percentage', 5, 2)->nullable();
		});
	}

	public function down()
	{
		Schema::drop('departments');
	}
}