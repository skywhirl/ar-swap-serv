<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTxnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('txns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('eth_txn');
            $table->string('eth_addr');
            $table->double('eth');
            $table->string('data');
            $table->string('ar_txn')->nullable();
            $table->double('ar')->nullable();
            $table->string('ar_addr');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('txns');
    }
}
