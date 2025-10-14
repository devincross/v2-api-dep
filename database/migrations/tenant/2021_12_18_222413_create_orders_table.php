<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_id');
            $table->foreignId('account_id')->constrained('accounts');
            $table->string('external_order_id')->nullable()->unique();
            $table->string('external_account_id')->nullable();
            $table->string('external_order_status')->nullable();
            $table->enum('status', ['waiting','pending','submitted','complete','error','changes']);
            $table->string('po')->nullable();
            $table->text('changes')->nullable();
            $table->uuid('dep_order_id')->nullable();
            $table->timestamp('dep_ordered_at')->nullable();
            $table->timestamp('dep_shipped_at')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
