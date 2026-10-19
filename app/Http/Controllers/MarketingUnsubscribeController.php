<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;

class MarketingUnsubscribeController extends Controller
{
    /**
     * Hủy nhận email tiếp thị bằng một lần bấm (link ký số trong thư, hoặc nút "Hủy đăng ký" của Gmail qua POST).
     */
    public function __invoke(Request $request, User $user)
    {
        if ($user->marketing_consent) {
            $user->setMarketingConsent(false);
            AuditService::log('marketing_unsubscribed', 'User', $user->id);
        }

        if ($request->isMethod('post')) {
            return response()->noContent();
        }

        return view('pages.unsubscribed', ['email' => $user->email]);
    }
}
