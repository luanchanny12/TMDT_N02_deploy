<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostgresCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_where_like_macro_executes_successfully(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Áo Polo Nam Classic',
            'status' => 'active',
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Đầm Dạ Hội Luxury',
            'status' => 'active',
        ]);

        $results = Product::whereLike('name', '%Polo%')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Áo Polo Nam Classic', $results->first()->name);

        $orResults = Product::whereLike('name', '%Polo%')
            ->orWhereLike('name', '%Dạ Hội%')
            ->get();
        $this->assertCount(2, $orResults);
    }

    public function test_query_builder_where_like_macro_adapts_to_driver(): void
    {
        $query = DB::table('products')->whereLike('name', '%test%');
        $sql = $query->toSql();

        // On SQLite/MySQL connection it generates LIKE
        $this->assertStringContainsString('like', strtolower($sql));
    }

    public function test_flash_sale_order_by_raw_handles_zero_price_gracefully(): void
    {
        $category = Category::factory()->create();

        // Tạo sản phẩm có price = 0 để kiểm tra không bị lỗi chia cho 0 (division by zero)
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Sản phẩm quà tặng 0đ',
            'price' => 0,
            'sale_price' => 0,
            'status' => 'active',
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Áo Sale 50%',
            'price' => 200000,
            'sale_price' => 100000,
            'status' => 'active',
            'sale_ends_at' => now()->addDay(),
        ]);

        // Thực thi query với CASE WHEN price > 0
        $products = Product::where('status', 'active')
            ->orderByRaw('(CASE WHEN price > 0 THEN (price - sale_price) / price ELSE 0 END) DESC')
            ->get();

        $this->assertNotEmpty($products);
        $this->assertCount(2, $products);
    }
}
