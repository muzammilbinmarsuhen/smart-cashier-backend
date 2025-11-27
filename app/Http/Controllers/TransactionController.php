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
    public function index(Request $request)
    {
        $transactions = Transaction::with('items.product')
            ->where('user_id', $request->user()->id)
            ->orderBy('transaction_date', 'desc')
            ->get();

        return response()->json([
            'message' => 'Daftar transaksi',
            'data'    => $transactions,
        ]);
    }

    // GET /api/transactions/{id}
    public function show(Request $request, $id)
    {
        $transaction = Transaction::with('items.product')
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $transaction) {
            return response()->json([
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail transaksi',
            'data'    => $transaction,
        ]);
    }

    // POST /api/transactions  (CHECKOUT)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.qty'          => 'required|integer|min:1',
            'paid_amount'          => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $itemsInput = $request->items;

        return DB::transaction(function () use ($user, $itemsInput, $request) {
            $total = 0;
            $detailItems = [];

            // Hitung total & cek stok
            foreach ($itemsInput as $item) {
                $product = Product::find($item['product_id']);

                if ($product->stock < $item['qty']) {
                    abort(response()->json([
                        'message' => 'Stok tidak cukup untuk produk: ' . $product->name,
                    ], 400));
                }

                $price    = $product->price;
                $subtotal = $price * $item['qty'];
                $total   += $subtotal;

                $detailItems[] = [
                    'product'  => $product,
                    'qty'      => $item['qty'],
                    'price'    => $price,
                    'subtotal' => $subtotal,
                ];
            }

            $paidAmount   = $request->paid_amount;
            $changeAmount = $paidAmount - $total;

            if ($changeAmount < 0) {
                abort(response()->json([
                    'message' => 'Uang bayar kurang. Total: ' . $total,
                ], 400));
            }

            // Generate nomor nota sederhana
            $invoice = 'TRX-' . now()->format('Ymd-His') . '-' . $user->id;

            // Simpan transaksi
            $transaction = Transaction::create([
                'user_id'          => $user->id,
                'invoice_number'   => $invoice,
                'total_amount'     => $total,
                'paid_amount'      => $paidAmount,
                'change_amount'    => $changeAmount,
                'transaction_date' => now(),
            ]);

            // Simpan item dan kurangi stok
            foreach ($detailItems as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id'     => $item['product']->id,
                    'qty'            => $item['qty'],
                    'price'          => $item['price'],
                    'subtotal'       => $item['subtotal'],
                ]);

                $item['product']->decrement('stock', $item['qty']);
            }

            $transaction->load('items.product');

            return response()->json([
                'message' => 'Transaksi berhasil dibuat',
                'data'    => $transaction,
            ], 201);
        });
    }
}
