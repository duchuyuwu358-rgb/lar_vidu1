<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hood;
use App\Models\Category;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Mail\PromotionMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminPortalController extends Controller
{
    private array $paidStatuses = ['completed', 'paid', 'success', 'da_thanh_toan', 'đã thanh toán', 'thành công', 'shipping', 'processing'];
    private array $pendingStatuses = ['pending', 'unpaid', 'cho_thanh_toan', 'chờ thanh toán'];
    private array $failedStatuses = ['cancelled', 'canceled', 'failed', 'da_huy', 'đã hủy', 'thất bại'];

    public function index(Request $request)
    {
        $period = $request->get('period', '1day');
        $selectedProductId = $request->get('product_id', 'all');

        $productFk = Schema::hasColumn('order_items', 'hood_id') ? 'hood_id' : 'product_id';
        $amountCol = Schema::hasColumn('orders', 'total_amount') ? 'total_amount' : 'total_price';

        $totalProducts   = Hood::count();
        $totalCategories = Category::count();
        $totalCustomers  = User::where('role', '!=', 'admin')->count();

        $totalSold = OrderItem::whereHas('order', function ($q) {
            $q->whereIn(DB::raw('LOWER(status)'), $this->paidStatuses);
        })->sum('quantity');

        $totalRevenue = Order::whereIn(DB::raw('LOWER(status)'), $this->paidStatuses)->sum($amountCol);

        $totalOrdersCount   = Order::count();
        $paidOrdersCount    = Order::whereIn(DB::raw('LOWER(status)'), $this->paidStatuses)->count();
        $pendingOrdersCount = Order::whereIn(DB::raw('LOWER(status)'), $this->pendingStatuses)->count();
        $failedOrdersCount  = Order::whereIn(DB::raw('LOWER(status)'), $this->failedStatuses)->count();

        $allProducts = Hood::select('id', 'name')->orderBy('name')->get();

        $topProducts = OrderItem::select(
            DB::raw("{$productFk} as product_id_fk"),
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(price * quantity) as total_amount')
        )->whereHas('order', function ($q) {
            $q->whereIn(DB::raw('LOWER(status)'), $this->paidStatuses);
        })
        ->groupBy($productFk)
        ->orderByDesc('total_qty')
        ->take(10)
        ->get();

        foreach ($topProducts as $item) {
            $item->hood = Hood::find($item->product_id_fk);
        }

        $chartData   = $this->getChartData($period, $selectedProductId, $productFk, $amountCol);
        $chartLabels = $chartData['labels'];
        $chartValues = $chartData['values'];

        $productName = 'Toàn bộ sản phẩm';
        if ($selectedProductId !== 'all') {
            $product = Hood::find($selectedProductId);
            if ($product) {
                $productName = $product->name;
            }
        }

        $periodText = match ($period) {
            '1day'   => 'Hôm nay (24 giờ)',
            '1week'  => '7 ngày gần đây',
            '1month' => '30 ngày gần đây',
            '1year'  => '12 tháng gần đây',
            'all'    => 'Toàn thời gian',
            default  => 'Hôm nay'
        };

        $chartTitle    = "Doanh thu: {$productName}";
        $chartSubtitle = "Thống kê theo {$periodText}";

        $recentOrders = Order::with('user')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        return view('admin.portal', compact(
            'totalProducts',
            'totalCategories',
            'totalCustomers',
            'totalSold',
            'totalRevenue',
            'paidOrdersCount',
            'pendingOrdersCount',
            'failedOrdersCount',
            'totalOrdersCount',
            'allProducts',
            'selectedProductId',
            'topProducts',
            'chartLabels',
            'chartValues',
            'chartTitle',
            'chartSubtitle',
            'period',
            'recentOrders'
        ));
    }

    private function getChartData(string $period, string $productId, string $productFk, string $amountCol): array
    {
        $labels = [];
        $values = [];
        $now    = Carbon::now();

        if ($period === '1day') {
            for ($h = 0; $h < 24; $h++) {
                $labels[] = sprintf('%02d:00', $h);
                $start    = $now->copy()->startOfDay()->addHours($h);
                $end      = $start->copy()->addHour();
                $values[] = $this->getRevenueBetween($start, $end, $productId, $productFk, $amountCol);
            }
        } elseif ($period === '1week') {
            for ($i = 6; $i >= 0; $i--) {
                $date     = $now->copy()->subDays($i);
                $labels[] = $date->format('d/m');
                $start    = $date->copy()->startOfDay();
                $end      = $date->copy()->endOfDay();
                $values[] = $this->getRevenueBetween($start, $end, $productId, $productFk, $amountCol);
            }
        } elseif ($period === '1month') {
            for ($i = 29; $i >= 0; $i--) {
                $date     = $now->copy()->subDays($i);
                $labels[] = $date->format('d/m');
                $start    = $date->copy()->startOfDay();
                $end      = $date->copy()->endOfDay();
                $values[] = $this->getRevenueBetween($start, $end, $productId, $productFk, $amountCol);
            }
        } elseif ($period === '1year') {
            for ($i = 11; $i >= 0; $i--) {
                $month    = $now->copy()->subMonths($i);
                $labels[] = $month->format('m/Y');
                $start    = $month->copy()->startOfMonth();
                $end      = $month->copy()->endOfMonth();
                $values[] = $this->getRevenueBetween($start, $end, $productId, $productFk, $amountCol);
            }
        } else {
            for ($i = 4; $i >= 0; $i--) {
                $year     = $now->copy()->subYears($i);
                $labels[] = $year->format('Y');
                $start    = $year->copy()->startOfYear();
                $end      = $year->copy()->endOfYear();
                $values[] = $this->getRevenueBetween($start, $end, $productId, $productFk, $amountCol);
            }
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function getRevenueBetween($start, $end, string $productId, string $productFk, string $amountCol)
    {
        if ($productId === 'all') {
            return Order::whereIn(DB::raw('LOWER(status)'), $this->paidStatuses)
                ->whereBetween('created_at', [$start, $end])
                ->sum($amountCol);
        }

        return OrderItem::where($productFk, $productId)
            ->whereHas('order', function ($q) use ($start, $end) {
                $q->whereIn(DB::raw('LOWER(status)'), $this->paidStatuses)
                  ->whereBetween('created_at', [$start, $end]);
            })
            ->sum(DB::raw('price * quantity'));
    }

    public function exportExcel(Request $request)
    {
        $status    = $request->get('status', 'all');
        $productId = $request->get('product_id', 'all');
        $startDate = $request->get('start_date');
        $endDate   = $request->get('end_date');
        $search    = trim($request->get('search', ''));

        $productFk = Schema::hasColumn('order_items', 'hood_id') ? 'hood_id' : 'product_id';
        $amountCol = Schema::hasColumn('orders', 'total_amount') ? 'total_amount' : 'total_price';

        $query = Order::with(['user', 'orderItems.hood', 'orderItems.product'])->orderBy('id', 'desc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%");

                if (Schema::hasColumn('orders', 'name')) {
                    $q->orWhere('name', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('orders', 'email')) {
                    $q->orWhere('email', 'like', "%{$search}%");
                }
                if (Schema::hasColumn('orders', 'phone')) {
                    $q->orWhere('phone', 'like', "%{$search}%");
                }

                $q->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        if ($status === 'paid') {
            $query->whereIn(DB::raw('LOWER(status)'), $this->paidStatuses);
        } elseif ($status === 'pending') {
            $query->whereIn(DB::raw('LOWER(status)'), $this->pendingStatuses);
        } elseif ($status === 'cancelled') {
            $query->whereIn(DB::raw('LOWER(status)'), $this->failedStatuses);
        }

        if ($productId !== 'all') {
            $query->whereHas('orderItems', function ($q) use ($productId, $productFk) {
                $q->where($productFk, $productId);
            });
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $orders   = $query->get();
        $fileName = 'bao_cao_doanh_thu_' . date('Y_m_d_H_i_s') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($orders, $amountCol) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, ['Mã đơn', 'Khách hàng', 'Email', 'Sản phẩm mua', 'Số lượng', 'Tổng tiền (VNĐ)', 'Trạng thái', 'Ngày đặt']);

            $grandTotal = 0;
            $grandQty   = 0;

            foreach ($orders as $order) {
                $customerName  = $order->name ?? $order->user->name ?? 'Khách lẻ';
                $customerEmail = $order->user->email ?? $order->email ?? '';

                $itemsList = [];
                $orderQty  = 0;

                foreach ($order->orderItems as $item) {
                    $pName = $item->hood->name ?? $item->product->name ?? ('Sản phẩm #' . ($item->hood_id ?? $item->product_id ?? ''));
                    $itemsList[] = $pName . " (x" . $item->quantity . ")";
                    $orderQty   += $item->quantity;
                }

                $totalAmount = $order->{$amountCol} ?? 0;
                $grandTotal += $totalAmount;
                $grandQty   += $orderQty;

                fputcsv($file, [
                    '#' . $order->id,
                    $customerName,
                    $customerEmail,
                    implode('; ', $itemsList),
                    $orderQty,
                    number_format($totalAmount, 0, ',', '.'),
                    $order->status,
                    $order->created_at ? $order->created_at->format('H:i d/m/Y') : ''
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['TỔNG CỘNG', '', '', '', $grandQty, number_format($grandTotal, 0, ',', '.') . ' đ', '', '']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bổ sung Giao diện Gửi Thư Hỗ Trợ Khách Hàng
     */
    public function showPromotionForm()
    {
        $customers = User::where('role', 'customer')->get();

        return view('admin.promotion_mail', compact('customers'));
    }

    /**
     * Thực hiện Gửi Email Hàng Loạt kèm Tệp đính kèm (PDF, DOC, DOCX, Hình ảnh)
     */
    public function sendPromotionMail(Request $request)
    {
        $request->validate([
            'subject'    => 'required|string|max:255',
            'content'    => 'required|string',
            'target'     => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // Tối đa 10MB
        ], [
            'subject.required' => 'Vui lòng nhập tiêu đề thư.',
            'content.required' => 'Vui lòng nhập nội dung thư.',
            'attachment.mimes' => 'Tập tin đính kèm phải có định dạng PDF, DOC, DOCX, JPG, JPEG hoặc PNG.',
            'attachment.max'   => 'Tập tin đính kèm không được vượt quá 10MB.',
        ]);

        $subject = $request->input('subject');
        $content = $request->input('content');
        $target  = $request->input('target');

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('temp_mail_attachments', 'public');
            $attachmentPath = storage_path('app/public/' . $path);
        }

        try {
            if ($target === 'all') {
                $emails = User::where('role', 'customer')->pluck('email')->filter()->toArray();
            } else {
                $emails = [$target];
            }

            foreach ($emails as $email) {
                Mail::to($email)->send(new PromotionMail($subject, $content, $attachmentPath));
            }

            // Xóa tập tin tạm sau khi đã gửi email hoàn tất
            if ($attachmentPath && file_exists($attachmentPath)) {
                @unlink($attachmentPath);
            }

            return back()->with('success', 'Đã gửi thư hỗ trợ kèm tập tin đính kèm thành công đến ' . count($emails) . ' khách hàng!');
        } catch (\Throwable $e) {
            if ($attachmentPath && file_exists($attachmentPath)) {
                @unlink($attachmentPath);
            }
            Log::error('Lỗi gửi thư hàng loạt: ' . $e->getMessage());
            return back()->with('error', 'Lỗi khi gửi mail: ' . $e->getMessage());
        }
    }
}