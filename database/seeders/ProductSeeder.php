<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Products for the two demo suppliers (ids 1-4 belong to Campus Bites, 5-8 to Fresh Corner),
     * plus one pending order so the supplier has something to accept right away.
     */
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');

        $catalog = [
            'supplier@lazyroom.com' => [
                ['Chicken shawarma wrap', 'Grilled chicken, garlic sauce and pickles.', 4.50, 40, 'Meals'],
                ['Margherita pizza slice', 'Tomato, mozzarella and basil.', 3.00, 30, 'Meals'],
                ['Fresh orange juice', 'Squeezed every morning, 330 ml.', 2.00, 25, 'Drinks'],
                ['Iced coffee', 'Cold brew with milk, 400 ml.', 2.50, 25, 'Drinks'],
            ],
            'fresh@lazyroom.com' => [
                ['Potato chips', 'Salted, 150 g bag.', 1.25, 60, 'Snacks'],
                ['Chocolate bar', 'Milk chocolate with hazelnuts, 100 g.', 1.00, 80, 'Snacks'],
                ['Instant noodles', 'Chicken flavor, pack of 5.', 0.90, 50, 'Groceries'],
                ['Bottled water', 'Pack of 6 x 500 ml.', 3.20, 35, 'Drinks'],
            ],
        ];

        foreach ($catalog as $email => $products) {
            $supplierId = User::where('email', $email)->value('id');
            foreach ($products as [$name, $description, $price, $stock, $category]) {
                Product::create([
                    'supplier_id' => $supplierId,
                    'category_id' => $categories[$category],
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'stock' => $stock,
                ]);
            }
        }

        // Sara's pending order from Campus Bites: 2 wraps and 1 orange juice (stock is reserved, as OrderController::store does).
        $sara = User::where('email', 'sara@lazyroom.com')->firstOrFail();
        $order = Order::create(['customer_id' => $sara->id, 'state' => 'pending', 'total_price' => 0]);
        $total = 0;
        foreach ([['Chicken shawarma wrap', 2], ['Fresh orange juice', 1]] as [$name, $quantity]) {
            $product = Product::where('name', $name)->firstOrFail();
            $order->items()->create(['product_id' => $product->id, 'quantity' => $quantity, 'price' => $product->price]);
            $product->decrement('stock', $quantity);
            $total += $product->price * $quantity;
        }
        $order->update(['total_price' => $total]);
    }
}
