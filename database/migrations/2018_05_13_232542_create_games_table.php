<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('ctime')->useCurrent();
            $table->timestamp('endtime')->nullable(true);
            $table->integer('columns')->unsigned();
            $table->integer('rows')->unsigned();
            $table->integer('mines')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->integer('free_spaces')->unsigned();
            $table->char('status', 10)->default('active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('games');
    }
}
