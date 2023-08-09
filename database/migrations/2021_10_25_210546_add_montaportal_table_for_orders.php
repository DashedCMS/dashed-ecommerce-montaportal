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
        Schema::create('dashed__order_montaportal', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('dashed__orders')
                ->cascadeOnDelete();
            $table->string('montaportal_id')
                ->nullable();
            $table->json('montaportal_pre_order_ids')
                ->nullable();
            $table->tinyInteger('pushed_to_montaportal')
                ->default(false);
            $table->string('track_and_trace_links')
                ->nullable();
            $table->tinyInteger('track_and_trace_present')
                ->default(false);
            $table->string('error')
                ->nullable();

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
