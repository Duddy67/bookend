<?php namespace Codalia\Bookend\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('codalia_bookend_categories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
	    $table->string('name')->nullable();
            $table->string('slug')->nullable()->index();
            $table->string('code')->nullable();
            $table->char('status', 15)->default('unpublished');
            $table->text('description')->nullable();
            $table->integer('parent_id')->unsigned()->index()->nullable();
            $table->integer('nest_left')->nullable();
            $table->integer('nest_right')->nullable();
            $table->integer('nest_depth')->nullable();
	    $table->integer('checked_out')->unsigned()->nullable()->index();
	    $table->timestamp('checked_out_time')->nullable();
            $table->timestamps();
        });

        Schema::create('codalia_bookend_categories_books', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('book_id')->unsigned();
            $table->integer('category_id')->unsigned();
            $table->primary(['book_id', 'category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('codalia_bookend_categories');
        Schema::dropIfExists('codalia_bookend_categories_books');
    }
}
