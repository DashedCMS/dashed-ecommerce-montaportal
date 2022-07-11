<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qcommerce__product_montaportal', function (Blueprint $table) {
            $table->dropForeign('qcommerce__product_montaportal_product_id_foreign');
            $table->foreignId('product_id')
                ->change()
                ->constrained('qcommerce__products')
                ->cascadeOnDelete();
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
};
