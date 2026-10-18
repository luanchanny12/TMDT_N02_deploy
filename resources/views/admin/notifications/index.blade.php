<x-layouts.admin :title="'Thông báo'" :header="'Thông báo'">
    @php
        $dot = ['info' => 'bg-sky-500', 'success' => 'bg-green-500', 'warning' => 'bg-amber-500', 'danger' => 'bg-red-500'];
    @endphp

    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-[#9a9490]">Đơn hàng mới, thanh toán, hủy đơn, hàng sắp hết, đánh giá chờ duyệt, yêu cầu hoàn tiền.</p>
        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
            @csrf
            <button class="text-sm text-[#b8847e] hover:underline">Đánh dấu tất cả đã đọc</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#efe8e3] divide-y divide-[#efe8e3]">
        @forelse($notifications as $n)
            <a href="{{ route('admin.notifications.open', $n->id) }}"
               class="flex items-start gap-3 px-5 py-4 hover:bg-[#faf7f4] {{ $n->read_at ? '' : 'bg-[#fdf9f6]' }}">
                <span class="mt-1.5 h-2.5 w-2.5 rounded-full flex-shrink-0 {{ $dot[$n->data['level'] ?? 'info'] ?? 'bg-sky-500' }}"></span>
                <span class="flex-1 min-w-0">
                    <span class="block text-sm {{ $n->read_at ? 'text-[#3d3d3d]' : 'font-semibold text-[#3d3d3d]' }}">{{ $n->data['title'] ?? '' }}</span>
                    <span class="block text-sm text-[#9a9490]">{{ $n->data['message'] ?? '' }}</span>
                </span>
                <span class="text-xs text-[#9a9490] whitespace-nowrap">{{ $n->created_at->diffForHumans() }}</span>
            </a>
        @empty
            <p class="px-5 py-10 text-center text-[#9a9490]">Chưa có thông báo nào.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
</x-layouts.admin>
