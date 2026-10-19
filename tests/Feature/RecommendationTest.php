<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::factory()->create();
    }

    private function product(string $name, array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'name' => $name,
            'category_id' => $this->category->id,
            'status' => 'active',
            'views_count' => 0,
            'stock' => 50,
        ], $attrs));
    }

    private function sell(Product $product, int $qty, string $status = 'delivered', ?Order $order = null): Order
    {
        $order ??= Order::factory()->create(['user_id' => User::factory()->create()->id, 'status' => $status]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name,
            'price' => 1000, 'quantity' => $qty, 'subtotal' => 1000 * $qty,
        ]);

        return $order;
    }

    private function review(Product $product, int $rating, int $times = 1): void
    {
        foreach (range(1, $times) as $i) {
            $user = User::factory()->create();
            Review::create([
                'product_id' => $product->id, 'user_id' => $user->id,
                'order_id' => Order::factory()->create(['user_id' => $user->id, 'status' => 'delivered'])->id,
                'rating' => $rating, 'comment' => 'Sản phẩm rất ổn, đúng mô tả.', 'status' => 'approved',
            ]);
        }
    }

    public function test_best_sellers_ignore_cancelled_orders(): void
    {
        $a = $this->product('Áo A');
        $b = $this->product('Áo B');
        $this->sell($a, 3);
        $this->sell($b, 10, 'cancelled');
        $this->sell($b, 1);

        $best = app(RecommendationService::class)->bestSellers();

        $this->assertSame(['Áo A', 'Áo B'], $best->pluck('name')->all());
    }

    public function test_top_rated_uses_bayesian_average(): void
    {
        $oneFive = $this->product('Một đánh giá 5 sao');
        $manyGood = $this->product('Ba mươi đánh giá 4.8');
        $this->review($oneFive, 5);
        $this->review($manyGood, 5, 24);
        $this->review($manyGood, 4, 6);

        $top = app(RecommendationService::class)->topRated();

        $this->assertSame('Ba mươi đánh giá 4.8', $top->first()->name);
    }

    public function test_most_viewed_and_hot_follow_real_signals(): void
    {
        $this->product('Ít xem', ['views_count' => 5]);
        $viewed = $this->product('Xem nhiều', ['views_count' => 900]);
        $seller = $this->product('Bán chạy', ['views_count' => 10]);
        $this->sell($seller, 200);

        $service = app(RecommendationService::class);

        $this->assertSame($viewed->id, $service->mostViewed()->first()->id);
        $this->assertSame($seller->id, $service->hot()->first()->id);
    }

    public function test_bought_together_comes_from_shared_orders(): void
    {
        $shirt = $this->product('Áo Polo');
        $shorts = $this->product('Quần Short');
        $bag = $this->product('Túi Tote');
        $other = $this->product('Không liên quan');

        foreach (range(1, 3) as $i) {
            $order = $this->sell($shirt, 1);
            $this->sell($shorts, 1, order: $order);
        }
        $order = $this->sell($shirt, 1);
        $this->sell($bag, 1, order: $order);
        $this->sell($other, 5);

        $together = app(RecommendationService::class)->boughtTogether($shirt);

        $this->assertSame(['Quần Short', 'Túi Tote'], $together->pluck('name')->all());

        $this->get(route('products.show', $shirt))
            ->assertOk()
            ->assertSee('Khách mua sản phẩm này cũng mua')
            ->assertSeeInOrder(['Khách mua sản phẩm này cũng mua', 'Quần Short']);
    }

    public function test_similar_products_prefer_same_category_ranked_by_popularity(): void
    {
        $base = $this->product('Áo gốc');
        $this->product('Cùng danh mục ít hot', ['views_count' => 1]);
        $this->product('Cùng danh mục hot', ['views_count' => 500]);
        $this->product('Khác danh mục rất hot', ['views_count' => 9999, 'category_id' => Category::factory()->create()->id]);

        $similar = app(RecommendationService::class)->similarTo($base, 2);

        $this->assertSame(['Cùng danh mục hot', 'Cùng danh mục ít hot'], $similar->pluck('name')->all());
    }

    public function test_home_shows_recommendation_tabs(): void
    {
        $p = $this->product('Giày Hot', ['views_count' => 300]);
        $this->sell($p, 4);
        $this->review($p, 5, 2);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Gợi ý cho bạn')
            ->assertSee('Đang hot')
            ->assertSee('Bán chạy')
            ->assertSee('Xem nhiều')
            ->assertSee('Đánh giá cao')
            ->assertSee('Giày Hot');
    }

    public function test_product_list_can_sort_by_popularity_signals(): void
    {
        $this->product('Ít bán', ['views_count' => 1]);
        $best = $this->product('Bán nhiều', ['views_count' => 2]);
        $this->sell($best, 9);
        $this->product('Xem nhiều nhất', ['views_count' => 800]);

        $this->get(route('products.index', ['sort' => 'best_selling']))->assertSeeInOrder(['Bán nhiều', 'Ít bán']);
        $this->get(route('products.index', ['sort' => 'most_viewed']))->assertSeeInOrder(['Xem nhiều nhất', 'Bán nhiều']);

        // Nút "Sản phẩm nổi bật" trên banner giờ thực sự sắp theo độ phổ biến
        $this->get(route('products.index', ['featured' => 1]))->assertSeeInOrder(['Bán nhiều', 'Ít bán']);
    }
}
