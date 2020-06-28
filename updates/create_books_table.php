<?php namespace Codalia\Bookend\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateBooksTable extends Migration
{
    public function up()
    {
        Schema::create('codalia_bookend_books', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
	    $table->string('title')->nullable();
            $table->string('slug')->index();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->char('status', 15)->default('unpublished');
	    $table->integer('category_id')->unsigned()->nullable()->index();
	    $table->integer('access_id')->unsigned()->nullable()->index();
	    $table->integer('created_by')->unsigned()->nullable()->index();
	    $table->integer('updated_by')->unsigned();
	    $table->timestamp('published_up')->nullable();
	    $table->timestamp('published_down')->nullable();
	    $table->integer('checked_out')->unsigned()->nullable()->index();
	    $table->timestamp('checked_out_time')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('codalia_bookend_books');
    }
}
