<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFaq;
use Illuminate\Http\Request;

class ChatbotFaqController extends Controller
{
    public function index(Request $request)
    {
        $query = ChatbotFaq::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('question', "%{$search}%")
                    ->orWhereLike('answer', "%{$search}%")
                    ->orWhereLike('keywords', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $faqs = $query->latest('sort_order')->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => ChatbotFaq::count(),
            'active' => ChatbotFaq::where('status', 'active')->count(),
        ];

        return view('admin.chatbot-faqs.index', compact('faqs', 'stats'));
    }

    public function create()
    {
        return view('admin.chatbot-faqs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:1000',
            'keywords' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        ChatbotFaq::create($validated);

        return redirect()->route('admin.chatbot-faqs.index')
            ->with('success', 'Tạo FAQ thành công!');
    }

    public function edit(ChatbotFaq $chatbotFaq)
    {
        $faq = $chatbotFaq;

        return view('admin.chatbot-faqs.edit', compact('faq'));
    }

    public function update(Request $request, ChatbotFaq $chatbotFaq)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:1000',
            'keywords' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $chatbotFaq->update($validated);

        return redirect()->route('admin.chatbot-faqs.index')
            ->with('success', 'Cập nhật FAQ thành công!');
    }

    public function destroy(ChatbotFaq $chatbotFaq)
    {
        $chatbotFaq->delete();

        return redirect()->route('admin.chatbot-faqs.index')
            ->with('success', 'Đã xóa FAQ!');
    }

    public function toggleStatus(ChatbotFaq $chatbotFaq)
    {
        $chatbotFaq->update([
            'status' => $chatbotFaq->status === 'active' ? 'inactive' : 'active',
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $chatbotFaq->fresh()->status,
            ]);
        }

        return back()->with('success', 'Cập nhật trạng thái thành công!');
    }
}
