<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $supplier;

    private int $productId;

    protected function setUp(): void
    {
        parent::setUp();

        Model::unguard();
        $this->customer = User::create(['name' => 'Customer', 'email' => 'c@test.dev', 'password' => 'password', 'role' => 'customer', 'balance' => 100]);
        $this->supplier = User::create(['name' => 'Supplier', 'email' => 's@test.dev', 'password' => 'password', 'role' => 'supplier', 'balance' => 0]);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Food', 'created_at' => now(), 'updated_at' => now()]);
        $this->productId = DB::table('products')->insertGetId([
            'supplier_id' => $this->supplier->id, 'category_id' => $categoryId, 'name' => 'Sandwich',
            'price' => 60, 'stock' => 10, 'created_at' => now(), 'updated_at' => now(),
        ]);
        Model::reguard();

        Sanctum::actingAs($this->customer);
    }

    private function acceptedOrder(float $price = 60): Order
    {
        $order = Order::create(['customer_id' => $this->customer->id, 'state' => 'accept', 'total_price' => $price]);
        DB::table('order_items')->insert([
            'order_id' => $order->id, 'product_id' => $this->productId, 'quantity' => 1, 'price' => $price,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $order;
    }

    public function test_completing_an_order_moves_the_money_to_the_supplier(): void
    {
        $order = $this->acceptedOrder();

        $this->postJson("/api/orders/{$order->id}/complete")->assertOk();

        $this->assertEquals(40, $this->customer->fresh()->balance);
        $this->assertEquals(60, $this->supplier->fresh()->balance);
        $this->assertSame('complete', $order->fresh()->state);
    }

    // Sequential repeat: rejected by the state check. Truly concurrent repeats (409) need MySQL row locks,
    // so they are covered by benchmarks/concurrency instead of this SQLite test.
    public function test_completing_the_same_order_again_does_not_charge_twice(): void
    {
        $order = $this->acceptedOrder();

        $this->postJson("/api/orders/{$order->id}/complete")->assertOk();
        $this->postJson("/api/orders/{$order->id}/complete")->assertStatus(400);

        $this->assertEquals(40, $this->customer->fresh()->balance);
        $this->assertEquals(60, $this->supplier->fresh()->balance);
    }

    public function test_an_order_is_not_completed_without_enough_balance(): void
    {
        $order = $this->acceptedOrder(150);

        $this->postJson("/api/orders/{$order->id}/complete")->assertStatus(400);

        $this->assertEquals(100, $this->customer->fresh()->balance);
        $this->assertEquals(0, $this->supplier->fresh()->balance);
        $this->assertSame('accept', $order->fresh()->state);
    }
}
