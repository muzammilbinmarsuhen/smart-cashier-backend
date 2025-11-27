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
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('invoice_number')->unique();   // contoh: TRX-20251127-0001
        $table->decimal('total_amount', 12, 2);       // total harga
        $table->decimal('paid_amount', 12, 2)->default(0); // uang dibayar
        $table->decimal('change_amount', 12, 2)->default(0); // kembalian
        $table->timestamp('transaction_date')->useCurrent();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
