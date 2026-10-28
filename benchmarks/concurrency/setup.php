<?php
// Creates one customer, one supplier and N accepted orders. Usage: php setup.php <balance> <orderCount> <orderPrice>
require __DIR__ . '/boot.php';

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

[, $balance, $count, $price] = $argv;
Model::unguard();
DB::statement('SET FOREIGN_KEY_CHECKS=0');
foreach (['order_items', 'orders', 'products', 'categories', 'users'] as $t) DB::table($t)->truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1');

$customer = User::create(['name' => 'Bench Customer', 'email' => 'c@bench.test', 'password' => bcrypt('x'), 'role' => 'customer', 'balance' => $balance]);
$supplier = User::create(['name' => 'Bench Supplier', 'email' => 's@bench.test', 'password' => bcrypt('x'), 'role' => 'supplier', 'balance' => 0]);
$categoryId = DB::table('categories')->insertGetId(['name' => 'Bench', 'created_at' => now(), 'updated_at' => now()]);
$productId = DB::table('products')->insertGetId([
    'supplier_id' => $supplier->id, 'category_id' => $categoryId, 'name' => 'Bench product',
    'price' => $price, 'stock' => 1000, 'created_at' => now(), 'updated_at' => now(),
]);
$ids = [];
foreach (range(1, (int) $count) as $i) {
    $order = Order::create(['customer_id' => $customer->id, 'state' => 'accept', 'total_price' => $price]);
    DB::table('order_items')->insert(['order_id' => $order->id, 'product_id' => $productId, 'quantity' => 1, 'price' => $price, 'created_at' => now(), 'updated_at' => now()]);
    $ids[] = $order->id;
}
echo json_encode(['customer' => $customer->id, 'supplier' => $supplier->id, 'orders' => $ids]), PHP_EOL;
