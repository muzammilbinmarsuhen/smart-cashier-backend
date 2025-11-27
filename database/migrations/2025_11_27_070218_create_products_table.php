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
    Schema::create('products', function (Blueprint $table) {
        $table->id();

        // Kolom utama produk
        $table->string('name');                 // nama produk
        $table->string('code')->unique();       // kode unik produk (barcode / sku)
        $table->decimal('price', 12, 2);        // harga
        $table->integer('stock')->default(0);   // stok
        $table->boolean('is_active')->default(true); // aktif / tidak

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
