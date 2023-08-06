<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrackAndTraceToMontaportal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dashed__order_montaportal', function (Blueprint $table) {
            $table->string('track_and_trace_links')->nullable();
            $table->tinyInteger('track_and_trace_present')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('montaportal', function (Blueprint $table) {
            //
        });
    }
}
