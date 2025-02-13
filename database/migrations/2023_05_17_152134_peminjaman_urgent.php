<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PeminjamanUrgent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('peminjaman_urgent', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('jenis_pinjaman');
            $table->string('alasan_pinjam');
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_per_month', 10, 2);
            $table->enum('status', ['Menunggu Bendahara', 'Menunggu Ketua', 'Disetujui','Ditolak'])->default('Menunggu Bendahara');
            $table->text('keterangan_tolak')->nullable();
            $table->string('status_pinjaman')->default('Belum Lunas');
            $table->integer('duration')->nullable(); // Jumlah bulan angsuran
            $table->date('repayment_date')->nullable();
            $table->string('ttd'); //Upload Scan Tanda Tangan
            $table->string('up_ket'); //Upload surat keterangan
            // $table->json('paid_months');
            $table->decimal('remaining_amount', 10, 2)->default(0);
            // $table->boolean('paid')->default(false);
            $table->decimal('total_paid_per_month', 10, 2)->default(0);
            // $table->json('payment_dates')->nullable();
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
        Schema::dropIfExists('peminjaman_urgent');
    }
}