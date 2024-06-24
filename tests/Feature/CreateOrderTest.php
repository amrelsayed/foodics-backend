<?php

namespace Tests\Feature;

use App\Mail\LowStockAlert;
use App\Models\Ingredient;
use App\Models\Product;
use Database\Seeders\IngredientSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_and_updates_stock() :void
    {
        $this->seed(IngredientSeeder::class);

        $beef = Ingredient::where('name', 'Beef')->first();
        $cheese = Ingredient::where('name', 'Cheese')->first();
        $onion = Ingredient::where('name', 'Onion')->first();

        $burger = Product::create(['name' => 'Burger']);
        $burger->ingredients()->attach($beef->id, ['quantity' => 150]);
        $burger->ingredients()->attach($cheese->id, ['quantity' => 30]);
        $burger->ingredients()->attach($onion->id, ['quantity' => 20]);

        $payload = [
            'products' => [
                ['product_id' => $burger->id, 'quantity' => 2]
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', ['id' => 1]);
        $this->assertDatabaseHas('product_ingredient', ['product_id' => $burger->id, 'ingredient_id' => $beef->id, 'quantity' => 150]);

        $beef->refresh();
        $cheese->refresh();
        $onion->refresh();

        $this->assertEquals(19700, $beef->stock); // 20000 - (150 * 2)
        $this->assertEquals(4940, $cheese->stock); // 5000 - (30 * 2)
        $this->assertEquals(960, $onion->stock); // 1000 - (20 * 2)
    }

    public function test_it_can_send_mail() :void
    {
        Mail::fake();
        
        $this->seed(IngredientSeeder::class);

        $beef = Ingredient::where('name', 'Beef')->first();
 
        $mailable = new  LowStockAlert($beef);

        $mailable->assertSeeInHtml($beef->name);
        $mailable->assertSeeInHtml($beef->stock);
    }

    public function test_it_sends_an_email_when_stock_is_below_50_percent()
    {
        Mail::fake();

        $this->seed(IngredientSeeder::class);

        $beef = Ingredient::where('name', 'Beef')->first();
        $cheese = Ingredient::where('name', 'Cheese')->first();
        $onion = Ingredient::where('name', 'Onion')->first();

        $burger = Product::create(['name' => 'Burger']);
        $burger->ingredients()->attach($beef->id, ['quantity' => 5000]); // 5kg per burger to trigger the alert
        $burger->ingredients()->attach($cheese->id, ['quantity' => 30]);
        $burger->ingredients()->attach($onion->id, ['quantity' => 20]);

        $payload = [
            'products' => [
                ['product_id' => $burger->id, 'quantity' => 2]
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);
        $response->assertStatus(200);

        $beef->refresh();
        $cheese->refresh();
        $onion->refresh();

        $this->assertEquals(10000, $beef->stock); // 20000 - (5000 * 2)
        $this->assertEquals(4940, $cheese->stock); // 5000 - (30 * 2)
        $this->assertEquals(960, $onion->stock); // 1000 - (20 * 2)

        Mail::assertSent(LowStockAlert::class, function ($mail) use ($beef) {
            return $mail->ingredient->id === $beef->id;
        });

        // Ensure no duplicate emails are sent
        Mail::fake();

        $payload = [
            'products' => [
                ['product_id' => $burger->id, 'quantity' => 1]
            ]
        ];

        $response = $this->postJson('/api/orders', $payload);
        $response->assertStatus(200);

        Mail::assertNothingSent();
    }
}
