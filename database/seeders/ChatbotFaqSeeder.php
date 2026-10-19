<?php

namespace Database\Seeders;

use App\Models\ChatbotFaq;
use Illuminate\Database\Seeder;

class ChatbotFaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Shop mở cửa mấy giờ?',
                'answer' => 'SocialShop mở cửa từ thứ 2 đến thứ 6, giờ làm việc từ 8:00 đến 17:00. Thứ 7 và chủ nhật nghỉ.',
                'keywords' => 'giờ mở cửa,mở cửa,nghỉ,mấy giờ,mấy giờ mở',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'question' => 'Shop có miễn phí ship không?',
                'answer' => 'Miễn phí giao hàng toàn quốc cho đơn từ 300.000đ. Đơn dưới 300.000đ phí ship 20.000đ (nội thành Hà Nội, TP.HCM) đến 35.000đ tùy khu vực.',
                'keywords' => 'ship,giao hàng,freeship,miễn phí ship,phí ship,phí giao,bao giờ nhận',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'question' => 'Shop hỗ trợ thanh toán những cách nào?',
                'answer' => 'Chúng tôi hỗ trợ 2 hình thức thanh toán: COD (thanh toán khi nhận hàng) và VNPay (thanh toán online).',
                'keywords' => 'thanh toán,payment,cod,vnpay,online,chuyển khoản',
                'status' => 'active',
                'sort_order' => 3,
            ],
            [
                'question' => 'Chính sách đổi trả như thế nào?',
                'answer' => 'Đổi trả trong vòng 7 ngày kể từ ngày nhận hàng. Sản phẩm phải còn nguyên tem, chưa qua sử dụng. Liên hệ hotline để được hỗ trợ.',
                'keywords' => 'đổi trả,return,tra hàng,đổi hàng,hoàn tiền,refund',
                'status' => 'active',
                'sort_order' => 4,
            ],
            [
                'question' => 'Liên hệ shop bằng cách nào?',
                'answer' => 'Hotline: 077123456 (8:00 - 17:00, T2-T6). Email: support@socialshop.vn. Bạn cũng có thể chat trực tiếp với mình!',
                'keywords' => 'liên hệ,hotline,email,support,gọi,hỗ trợ,tư vấn',
                'status' => 'active',
                'sort_order' => 5,
            ],
            [
                'question' => 'Bao lâu thì nhận được hàng?',
                'answer' => 'Nội thành: 1-2 ngày. Ngoại thành: 3-5 ngày. Toàn quốc: 5-7 ngày làm việc.',
                'keywords' => 'bao lâu,nhận hàng,giao bao lâu,thời gian giao,nhận hàng bao lâu',
                'status' => 'active',
                'sort_order' => 6,
            ],
            [
                'question' => 'Giá cả sản phẩm như thế nào?',
                'answer' => 'Bạn có thể xem giá chi tiết từng sản phẩm tại trang Sản phẩm trên website. Shop có thời trang nam nữ, giày dép, túi xách, đồng hồ và mỹ phẩm với giá từ 150.000đ đến khoảng 16.000.000đ.',
                'keywords' => 'giá,giá cả,bao nhiêu,price,cost,tiền,có giá',
                'status' => 'active',
                'sort_order' => 7,
            ],
        ];

        foreach ($faqs as $faq) {
            ChatbotFaq::create($faq);
        }
    }
}
