<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('absen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pekerja');
            $table->foreignId('id_perusahaan');
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->timestamp('update_at')->useCurrent();

            // Define foreign key constraints
            $table->foreign('id_pekerja')->references('id')->on('pekerja')->onDelete('cascade');
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
        Schema::dropIfExists('absen');
    }
};
