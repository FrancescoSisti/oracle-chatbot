<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('training_data', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->string('category');
            $table->boolean('is_verified')->default(false);
            $table->float('confidence_score')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('training_data');
    }
};
