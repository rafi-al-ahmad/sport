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
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->integer('quantity');
            $table->unsignedBigInteger('product_id');
            $table->double('price');
            $table->double('compareAtPrice')->nullable();
            $table->boolean('is_default');
            $table->json('options')->nullable();
            $table->json('languages');
            $table->json('videos');
            $table->softDeletes();
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
        Schema::dropIfExists('variants');
    }
};
