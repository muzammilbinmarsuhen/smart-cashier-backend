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
    Schema::create('transaction_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_id')->constrained()->onDelete('restrict');
        $table->integer('qty');
        $table->decimal('price', 12, 2);          // harga satuan saat transaksi
        $table->decimal('subtotal', 12, 2);       // qty * price
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
