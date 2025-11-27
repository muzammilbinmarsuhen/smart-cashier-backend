<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    // GET /api/transactions
    public function index()
    {
        $transactions = Transaction::with('items.product', 'user')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Daftar transaksi',
            'data'    => $transactions,
        ]);
    }

    // POST /api/transactions
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items'       => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'paid_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $totalAmount = 0;
        $itemsData = [];

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            if ($product->stock < $item['qty']) {
                return response()->json([
                    'message' => 'Stok produk ' . $product->name . ' tidak cukup',
                ], 400);
            }
            $subtotal = $product->price * $item['qty'];
            $totalAmount += $subtotal;
            $itemsData[] = [
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
                'price' => $product->price,
                'subtotal' => $subtotal,
            ];
        }

        if ($request->paid_amount < $totalAmount) {
            return response()->json([
                'message' => 'Uang dibayar kurang dari total',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Generate invoice number
            $invoiceNumber = 'TRX-' . date('Ymd') . '-' . str_pad(Transaction::count() + 1, 4, '0', STR_PAD_LEFT);

            $transaction = Transaction::create([
                'user_id' => auth()->id(),
                'invoice_number' => $invoiceNumber,
                'total_amount' => $totalAmount,
                'paid_amount' => $request->paid_amount,
                'change_amount' => $request->paid_amount - $totalAmount,
            ]);

            foreach ($itemsData as $itemData) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $itemData['product_id'],
                    'qty' => $itemData['qty'],
                    'price' => $itemData['price'],
                    'subtotal' => $itemData['subtotal'],
                ]);

                // Update stock
                $product = Product::find($itemData['product_id']);
                $product->decrement('stock', $itemData['qty']);
            }

            DB::commit();

            $transaction->load('items.product');

            return response()->json([
                'message' => 'Transaksi berhasil dibuat',
                'data'    => $transaction,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    // GET /api/transactions/{id}
    public function show($id)
    {
        $transaction = Transaction::with('items.product', 'user')->find($id);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail transaksi',
            'data'    => $transaction,
        ]);
    }

    // PUT /api/transactions/{id} - maybe not needed for cashier, but for completeness
    public function update(Request $request, $id)
    {
        // Implement if needed, but for now, perhaps not allow updates
        return response()->json([
            'message' => 'Update transaksi tidak diizinkan',
        ], 405);
    }

    // DELETE /api/transactions/{id}
    public function destroy($id)
    {
        $transaction = Transaction::with('items')->find($id);

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Restore stock
            foreach ($transaction->items as $item) {
                $item->product->increment('stock', $item->qty);
            }

            $transaction->delete();

            DB::commit();

            return response()->json([
                'message' => 'Transaksi berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus transaksi: ' . $e->getMessage(),
            ], 500);
        }
    }
}
