<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DM Sans', Arial, sans-serif; color: #3d3d3d; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #b8847e; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #faf7f4; padding: 20px; border: 1px solid #efe8e3; }
        .footer { background-color: #efe8e3; padding: 15px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; color: #6b6560; }
        .btn { display: inline-block; background-color: #b8847e; color: white !important; padding: 10px 22px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .box { background: white; border: 1px solid #efe8e3; border-radius: 6px; padding: 12px 15px; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        td { padding: 8px; border-bottom: 1px solid #efe8e3; }
        .code { font-size: 20px; font-weight: bold; color: #b8847e; letter-spacing: 1px; }
        a { color: #b8847e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>@yield('heading')</h1>
        </div>
        <div class="content">
            <p>Xin chào {{ $user->name }},</p>
            @yield('body')
        </div>
        <div class="footer">
            <p>SocialShop · Hotline 077123456 · support@socialshop.vn</p>
            <p>
                Bạn nhận thư này vì đã đồng ý nhận email ưu đãi từ SocialShop.
                <a href="{{ $unsubscribeUrl }}">Hủy đăng ký nhận email</a>
                · <a href="{{ route('pages.privacy') }}">Chính sách bảo mật</a>
            </p>
        </div>
    </div>
</body>
</html>
