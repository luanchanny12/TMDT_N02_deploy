<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Dữ liệu demo: đơn hàng nhiều trạng thái trong 14 ngày gần nhất + đánh giá
 * (đã duyệt / chờ duyệt / từ chối) để dashboard doanh thu và hàng đợi kiểm duyệt có số liệu.
 */
class DemoOrderSeeder extends Seeder
{
    private const COMMENTS = [
        5 => ['Sản phẩm tuyệt vời, đúng mô tả. Giao hàng nhanh!', 'Rất hài lòng, sẽ ủng hộ shop tiếp.', 'Chất lượng vượt mong đợi, đóng gói cẩn thận.'],
        4 => ['Hàng tốt, giao hơi chậm một chút.', 'Đáng tiền, chỉ tiếc là hộp hơi móp.', 'Dùng ổn, shop tư vấn nhiệt tình.'],
        3 => ['Tạm ổn so với giá tiền.', 'Sản phẩm bình thường, không có gì nổi bật.'],
        2 => ['Màu hơi khác so với hình.'],
    ];

    public function run(): void
    {
        mt_srand(2026);

        $customers = collect([
            ['name' => 'Trần Thị Bình', 'email' => 'binh@socialshop.vn'],
            ['name' => 'Lê Hoàng Cường', 'email' => 'cuong@socialshop.vn'],
            ['name' => 'Phạm Minh Dung', 'email' => 'dung@socialshop.vn'],
            ['name' => 'Võ Quốc Em', 'email' => 'em@socialshop.vn'],
        ])->map(fn ($c, $i) => User::create([
            'name' => $c['name'],
            'email' => $c['email'],
            'password' => Hash::make('password'),
            'role' => 'customer',
            'referral_code' => 'DEMO000'.($i + 1),
            'email_verified_at' => now(),
        ]));

        $mainCustomer = User::where('email', 'user@socialshop.vn')->first();
        if ($mainCustomer) {
            $customers->prepend($mainCustomer);
        }

        $products = Product::where('status', 'active')->get();

        // [số ngày trước, trạng thái, phương thức]
        $plan = [
            [13, 'delivered', 'cod'], [12, 'delivered', 'vnpay'], [11, 'delivered', 'cod'],
            [10, 'cancelled', 'cod'], [9, 'delivered', 'vnpay'], [8, 'delivered', 'cod'],
            [7, 'delivered', 'cod'], [6, 'delivered', 'vnpay'], [6, 'delivered', 'cod'],
            [5, 'delivered', 'cod'], [5, 'shipping', 'vnpay'], [4, 'delivered', 'cod'],
            [4, 'cancelled', 'vnpay'], [3, 'shipping', 'cod'], [3, 'delivered', 'vnpay'],
            [2, 'confirmed', 'cod'], [2, 'confirmed', 'vnpay'], [1, 'shipping', 'cod'],
            [1, 'pending', 'cod'], [0, 'pending', 'cod'], [0, 'confirmed', 'vnpay'],
        ];

        foreach ($plan as $n => [$daysAgo, $status, $method]) {
            $customer = $customers[$n % $customers->count()];
            $createdAt = now()->subDays($daysAgo)->setTime(mt_rand(8, 21), mt_rand(0, 59));

            $this->createOrder($customer, $products->random(mt_rand(1, 3)), $status, $method, $createdAt);
        }

        $this->seedReviews();

        // Lượt xem để mục "Xem nhiều nhất" trên dashboard có dữ liệu
        foreach ($products as $product) {
            $product->update(['views_count' => mt_rand(5, 500)]);
        }
    }

    private function createOrder(User $customer, $items, string $status, string $method, $createdAt): void
    {
        $lines = $items->map(function (Product $product) {
            $quantity = mt_rand(1, 2);
            $price = $product->effectivePrice();

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $price * $quantity,
            ];
        });

        $subtotal = $lines->sum('subtotal');
        $shippingFee = $subtotal >= 500000 ? 0 : 30000;

        // VNPay: đơn bị hủy = thanh toán thất bại; còn lại đã thanh toán.
        // COD: chỉ "đã thanh toán" khi đã giao.
        $paymentStatus = match (true) {
            $method === 'vnpay' => $status === 'cancelled' ? 'failed' : 'paid',
            default => $status === 'delivered' ? 'paid' : 'pending',
        };

        $order = new Order;
        $order->forceFill([
            'user_id' => $customer->id,
            'order_code' => 'ORD-'.$createdAt->format('Ymd').'-'.strtoupper(Str::random(4)),
            'subtotal' => $subtotal,
            'discount' => 0,
            'shipping_fee' => $shippingFee,
            'total' => $subtotal + $shippingFee,
            'status' => $status,
            'payment_method' => $method,
            'payment_status' => $paymentStatus,
            'shipping_name' => $customer->name,
            'shipping_phone' => '09'.mt_rand(10000000, 99999999),
            'shipping_address' => mt_rand(1, 200).' Nguyễn Trãi, Thanh Xuân',
            'shipping_province' => 'Hà Nội',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        $order->items()->createMany($lines->all());

        Payment::create([
            'order_id' => $order->id,
            'method' => $method,
            'transaction_code' => $method === 'vnpay' ? 'VNP-DEMO-'.$order->id : null,
            'amount' => $order->total,
            'status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'paid' ? $createdAt : null,
        ]);

        // Đơn còn hiệu lực thì trừ kho cho khớp số liệu
        if ($status !== 'cancelled') {
            foreach ($lines as $line) {
                Product::whereKey($line['product_id'])->decrement('stock', $line['quantity']);
            }
        }
    }

    private function seedReviews(): void
    {
        $deliveredOrders = Order::with('items')->where('status', 'delivered')->get();
        $i = 0;

        foreach ($deliveredOrders as $order) {
            foreach ($order->items as $item) {
                $exists = Review::where('user_id', $order->user_id)
                    ->where('product_id', $item->product_id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $rating = [5, 5, 4, 5, 4, 3, 5, 2][$i % 8];
                // Phần lớn đã duyệt, vài đánh giá chờ duyệt để demo kiểm duyệt, 1 bị từ chối
                $status = match (true) {
                    $i % 4 === 3 => 'pending',
                    $i === 6 => 'rejected',
                    default => 'approved',
                };

                $comments = self::COMMENTS[$rating];

                Review::create([
                    'user_id' => $order->user_id,
                    'product_id' => $item->product_id,
                    'order_id' => $order->id,
                    'rating' => $rating,
                    'comment' => $comments[$i % count($comments)],
                    'status' => $status,
                ]);

                $i++;
            }
        }
    }
}
