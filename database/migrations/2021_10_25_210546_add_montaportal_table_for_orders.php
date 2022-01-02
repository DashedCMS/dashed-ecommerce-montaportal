<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMontaportalTableForOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qcommerce__order_montaportal', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained('qcommerce__orders');
            $table->string('montaportal_id')->nullable();
            $table->json('montaportal_pre_order_ids')->nullable();
            $table->tinyInteger('pushed_to_montaportal')->default(0);

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
        //
    }
}
