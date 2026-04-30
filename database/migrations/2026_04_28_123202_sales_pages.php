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
        Schema::create('sales_pages', function (Blueprint $table) {
            $table->id();

            // relasi ke user (auth)
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // nama produk biar gampang ditampilkan di list
            $table->string('product_name');

            // data input dari user (form)
            $table->json('input_data');

            // hasil AI (headline, benefits, dll)
            $table->json('generated_content');

            // optional (buat future improvement)
            $table->string('template')->nullable(); // misal: modern, minimal

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_pages');
    }
};
