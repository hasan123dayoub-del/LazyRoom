<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /** Store a new order and deduct stock for each item. */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::create(['customer_id' => Auth::id(), 'state' => 'pending', 'total_price' => 0]);
            $totalPrice = 0;

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                if ($product->stock < $item['quantity']) throw new \Exception("{$product->name} out of stock.");

                $subtotal = $product->price * $item['quantity'];
                $totalPrice += $subtotal;
                $order->items()->create(['product_id' => $product->id, 'quantity' => $item['quantity'], 'price' => $product->price]);
                $product->decrement('stock', $item['quantity']);
            }
            $order->update(['total_price' => $totalPrice]);
            DB::commit();
            return response()->json(['message' => 'Order placed successfully', 'order' => $order]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    /** Cancel a pending order and restore stock levels. */
    public function cancel($id)
    {
        $order = Order::where('id', $id)->where('customer_id', Auth::id())->firstOrFail();
        if ($order->state !== 'pending') return response()->json(['message' => 'Cannot cancel'], 400);

        DB::beginTransaction();
        foreach ($order->items as $item) $item->product->increment('stock', $item->quantity);
        $order->update(['state' => 'cancelled']);
        DB::commit();
        return response()->json(['message' => 'Order cancelled']);
    }
    /** Mark an accepted order as complete (finalized). */
    public function complete($id)
    {
        $order = Order::with('items.product')->where('id', $id)->where('customer_id', Auth::id())->firstOrFail();
        if ($order->state !== 'accept') return response()->json(['message' => 'Not accepted yet'], 400);

        DB::beginTransaction();
        $order->update(['state' => 'complete']);
        DB::commit();
        return response()->json(['message' => 'Order completed']);
    }
    /** Get all orders placed by the currently authenticated customer. */
    public function myOrders()
    {
        return response()->json(['orders' => Order::where('customer_id', Auth::id())->with('items.product')->latest()->get()]);
    }
    /** Get all orders that contain products belonging to the current supplier. */
    public function supplierIndex()
    {
        $supplierId = Auth::id();

        $orders = Order::whereHas('items.product', function ($query) use ($supplierId) {
            $query->where('supplier_id', $supplierId);
        })
            ->with([
                'customer:id,name,building,room_number',
                'items' => function ($query) use ($supplierId) {
                    $query->whereHas('product', function ($q) use ($supplierId) {
                        $q->where('supplier_id', $supplierId);
                    })->with('product');
                }
            ])
            ->latest()
            ->get()
            ->map(function ($order) {
                $customerName = $order->customer ? $order->customer->name : 'Unknown Customer';
                $building = $order->customer ? $order->customer->building : 'N/A';
                $roomNumber = $order->customer ? $order->customer->room_number : 'N/A';

                return [
                    'order_id' => $order->id,
                    'state' => $order->state,
                    'customer_name' => $customerName,
                    'delivery_address' => [
                        'building' => $building,
                        'room_number' => $roomNumber
                    ],
                    'created_at' => $order->created_at,

                    'my_products_total' => (float) $order->items->sum(function ($item) {
                        return $item->price * $item->quantity;
                    }),

                    'items' => $order->items->map(function ($item) {
                        return [
                            'product_name' => $item->product ? $item->product->name : 'Product Deleted',
                            'quantity' => $item->quantity,
                            'price' => (float) $item->price,
                            'subtotal' => (float) ($item->price * $item->quantity)
                        ];
                    })
                ];
            });

        return response()->json([
            "status" => "success",
            "message" => "Customer orders retrieved successfully for this supplier",
            "orders" => $orders
        ], 200);
    }

    /** Accept a pending order, transfer balance from customer to supplier. */
    public function accept($id)
    {
        $user = Auth::user();
        $order = Order::with(['customer', 'items.product'])->findOrFail($id);

        if ($order->state !== 'pending') {
            return response()->json(["status" => "error", "message" => "Order is not pending."], 400);
        }

        foreach ($order->items as $item) {
            if ($item->product->supplier_id !== $user->id) {
                return response()->json(["status" => "error", "message" => "Unauthorized."], 403);
            }
        }

        DB::beginTransaction();
        try {
            User::where('id', $order->customer_id)->decrement('balance', $order->total_price);

            User::where('id', $user->id)->increment('balance', $order->total_price);

            $order->update(['state' => 'accept']);

            DB::commit();
            return response()->json(["status" => "success", "message" => "Order accepted."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["status" => "error", "message" => "Server error: " . $e->getMessage()], 500);
        }
    }

    /** Reject a pending order and return items to stock. */
    public function reject($id)
    {
        $user = Auth::user();
        $order = Order::with('items.product')->findOrFail($id);

        if ($order->state !== 'pending') {
            return response()->json(["status" => "error", "message" => "Order cannot be rejected."], 400);
        }

        foreach ($order->items as $item) {
            if ($item->product->supplier_id !== $user->id) {
                return response()->json(["status" => "error", "message" => "Unauthorized."], 403);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }
            $order->update(['state' => 'reject']);

            DB::commit();
            return response()->json(["status" => "success", "message" => "Order rejected."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["status" => "error", "message" => "Server error."], 500);
        }
    }
}
