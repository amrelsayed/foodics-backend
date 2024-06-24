<?php

namespace App\Http\Controllers;

use App\Mail\LowStockAlert;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CreateOrderAction extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $order = Order::create();

        foreach ($request->products as $request_product) {
            $product = Product::find($request_product['product_id']);

            $order->products()->attach($product->id, ['quantity' => $request_product['quantity']]);

            foreach ($product->ingredients as $ingredient) {
                $ingredient->stock -= $ingredient->pivot->quantity * $request_product['quantity'];
                $ingredient->save();

                if ($ingredient->stock <= ($ingredient->stock_threshold * 0.5 && !$ingredient->alert_sent)) {
                    Mail::to(env('MERCHENT_EMAIL'),)->send(new LowStockAlert($ingredient));
                    $ingredient->alert_sent = true;
                    $ingredient->save();
                }
            }
        }

        return response()->json(['message' => 'Order created successfully']);
    }
}
