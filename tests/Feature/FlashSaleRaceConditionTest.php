<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class FlashSaleRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    public function test_flash_sale_orders_cannot_make_stock_negative()
    {
        $product = Product::create([
            'name' => 'Flash Sale Product',
            'price' => 50000,
            'stock' => 10,
        ]);

        $successfulOrders = 0;
        $failedOrders = 0;

        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson('/api/orders', [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

            if ($response->getStatusCode() === 201) {
                $successfulOrders++;
            } elseif ($response->getStatusCode() === 409) {
                $failedOrders++;
            } else {
                $this->fail("Unexpected response status code: " . $response->getStatusCode() . ". Response content: " . $response->getContent());
            }
        }

        $product->refresh();

        $this->assertEquals(10, $successfulOrders);
        $this->assertEquals(20, $failedOrders);
        $this->assertEquals(0, $product->stock);
        $this->assertGreaterThanOrEqual(0, $product->stock);
        $this->assertEquals(10, Order::count());
        $this->assertEquals(10, OrderItem::count());
    }

    public function test_order_requires_at_least_one_item()
    {
        $response = $this->postJson('/api/orders', [
            'items' => []
        ]);

        $response->assertStatus(422);
    }
}
