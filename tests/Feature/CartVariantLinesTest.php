<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartVariantLinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_holds_two_sizes_of_same_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['category_id' => Category::factory()->create()->id, 'status' => 'active', 'stock' => 10]);
        $m = ProductVariant::create(['product_id' => $product->id, 'size' => 'M', 'stock' => 5]);
        $l = ProductVariant::create(['product_id' => $product->id, 'size' => 'L', 'stock' => 5]);

        app(CartService::class)->addItem($user->id, $product->id, 1, $m->id);
        app(CartService::class)->addItem($user->id, $product->id, 1, $l->id);
        app(CartService::class)->addItem($user->id, $product->id, 1, $l->id);

        $this->assertSame(2, CartItem::count());
        $this->assertSame(2, CartItem::where('variant_id', $l->id)->value('quantity'));
    }
}
