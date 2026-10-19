<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeCategoryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_category_counts_active_products_of_its_children(): void
    {
        $parent = Category::factory()->create(['name' => 'Thời trang nam', 'parent_id' => null, 'status' => 'active']);
        $shirts = Category::factory()->create(['name' => 'Áo nam', 'parent_id' => $parent->id, 'status' => 'active']);
        $pants = Category::factory()->create(['name' => 'Quần nam', 'parent_id' => $parent->id, 'status' => 'active']);

        Product::factory()->count(3)->create(['category_id' => $shirts->id, 'status' => 'active']);
        Product::factory()->create(['category_id' => $pants->id, 'status' => 'active']);
        Product::factory()->create(['category_id' => $pants->id, 'status' => 'inactive']); // ẩn → không tính

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder(['Thời trang nam', '4 sản phẩm']);
    }
}
