<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_perusahaan');
            $table->string('email');
            $table->string('password');
            $table->string('nama');
            $table->date('tanggal_lahir');
            $table->string('profile')->nullable();

            // Define foreign key constraint
            $table->foreign('id_perusahaan')->references('id')->on('perusahaan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admin');
    }
};
