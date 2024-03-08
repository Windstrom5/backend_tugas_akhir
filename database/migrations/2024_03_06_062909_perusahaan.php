<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('perusahaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->double('latitude');
            $table->double('longitude');
            $table->string('jam_masuk');
            $table->string('jam_keluar');
            $table->string('batas_aktif');
            $table->string('logo')->nullable();
            $table->string('secret_key');

            // You can add more columns based on your requirements

            $table->unique('secret_key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('perusahaan');
    }
};
