<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_id')->index();
            $table->string('call_transaction_id')->nullable();
            $table->string('call');
            $table->longText('payload')->nullable();
            $table->longText('response')->nullable();
            $table->string('response_code')->nullable();
            $table->longText('response_msg')->nullable();
            $table->enum('response_status', ['complete', 'error'])->nullable();
            $table->timestamp('response_at')->nullable();
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
        Schema::dropIfExists('transactions');
    }
}
