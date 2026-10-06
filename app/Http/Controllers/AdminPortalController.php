<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hood;
use App\Models\Category;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdminPortalController extends Controller
{
    /**
     * Danh sách trạng thái đơn hàng dùng chung hệ thống
     */
    private array $paidStatuses = ['completed', 'paid', 'success', 'da_thanh_toan', 'đã thanh toán', 'thành công', 'shipping', 'processing'];
    private array $pendingStatuses = ['pending', 'unpaid', 'cho_thanh_toan', 'chờ thanh toán'];
    private array $failedStatuses = ['cancelled', 'canceled', 'failed', 'da_huy', 'đã hủy', 'thất bại'];

    public function index(Request $request)
    {
        $period = $request->get('period', '1day');
        $selectedProductId = $request->get('product_id', 'all');

        // Tự động nhận diện tên cột trong Database (tránh lỗi lệch tên cột)
        $productFk = Schema::hasColumn('order_items', 'hood_id') ? 'hood_id' : 'product_id';
        $amountCol = Schema::hasColumn('orders', 'total_amount') ? 'total_amount' : 'total_price';

        // 1. Thống kê số lượng tổng quan
        $totalProducts   = Hood::count();
        $totalCategories = Category::count();
        $totalCustomers  = User::where('role', '!=', 'admin')->count();

        // Tổng số lượng sản phẩm đã bán (từ các đơn đã thanh toán/hoàn thành)
        $totalSold = OrderItem::whereHas('order', function ($q) {
            $q->whereIn(DB::raw('LOWER(status)'), $this->paidStatuses);
        })->sum('quantity');

        // Tổng doanh thu thực tế
        $totalRevenue = Order::whereIn(DB::raw('LOWER(status)'), $this->paidStatuses)->sum($amountCol);

        // 2. Thống kê trạng thái đơn hàng
        $totalOrdersCount   = Order::count();
        $paidOrdersCount    = Order::whereIn(DB::raw('LOWER(status)'), $this->paidStatuses)->count();
        $pendingOrdersCount = Order::whereIn(DB::raw('LOWER(status)'), $this->pendingStatuses)->count();
        $failedOrdersCount  = Order::whereIn(DB::raw('LOWER(status)'), $this->failedStatuses)->count();

        // 3. Danh sách sản phẩm dùng cho Dropdown lọc biểu đồ
        $allProducts = Hood::select('id', 'name')->orderBy('name')->get();

        // 4. Bảng Top 10 Sản Phẩm Bán Chạy
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

        // 5. Dữ liệu Biểu đồ Doanh thu
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

        // 6. ĐƠN HÀNG GẦN ĐÂY: Sắp xếp ID GIẢM DẦN (Mới nhất lên đầu: #25 -> #24 -> #23)
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

    /**
     * Dữ liệu vẽ biểu đồ doanh thu theo khoảng thời gian
     */
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
        } else { // 'all'
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

    /**
     * Tính tổng doanh thu trong khoảng thời gian cụ thể
     */
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

    /**
     * Xuất Báo cáo Đơn hàng & Doanh thu ra file CSV/Excel (Chuẩn mã hóa UTF-8 BOM)
     */
    public function exportExcel(Request $request)
    {
        $status    = $request->get('status', 'all');
        $productId = $request->get('product_id', 'all');
        $startDate = $request->get('start_date');
        $endDate   = $request->get('end_date');
        $search    = $request->get('search');

        $productFk = Schema::hasColumn('order_items', 'hood_id') ? 'hood_id' : 'product_id';
        $amountCol = Schema::hasColumn('orders', 'total_amount') ? 'total_amount' : 'total_price';

        // Xuất file sắp xếp mới nhất lên đầu
        $query = Order::with(['user', 'orderItems.hood', 'orderItems.product'])->orderBy('id', 'desc');

        // 1. Tìm kiếm theo Từ khóa
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Lọc theo Trạng thái
        if ($status === 'paid') {
            $query->whereIn(DB::raw('LOWER(status)'), $this->paidStatuses);
        } elseif ($status === 'pending') {
            $query->whereIn(DB::raw('LOWER(status)'), $this->pendingStatuses);
        } elseif ($status === 'cancelled') {
            $query->whereIn(DB::raw('LOWER(status)'), $this->failedStatuses);
        }

        // 3. Lọc theo Sản phẩm
        if ($productId !== 'all') {
            $query->whereHas('orderItems', function ($q) use ($productId, $productFk) {
                $q->where($productFk, $productId);
            });
        }

        // 4. Lọc theo khoảng ngày
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
            fputs($file, "\xEF\xBB\xBF"); // BOM chống lỗi font tiếng Việt trên Microsoft Excel

            fputcsv($file, ['Mã đơn', 'Khách hàng', 'Email', 'Sản phẩm mua', 'Số lượng', 'Tổng tiền (VNĐ)', 'Trạng thái', 'Ngày đặt']);

            $grandTotal = 0;
            $grandQty   = 0;

            foreach ($orders as $order) {
                $customerName  = $order->name ?? $order->user->name ?? $order->customer_name ?? 'Khách lẻ';
                $customerEmail = $order->user->email ?? $order->email ?? $order->customer_email ?? '';

                $itemsList = [];
                $orderQty  = 0;

                foreach ($order->orderItems as $item) {
                    $pName = $item->hood->name ?? $item->product->name ?? ('Sản phẩm #' . ($item->hood_id ?? $item->product_id ?? ''));
                    $itemsList[] = $pName . " (x" . $item->quantity . ")";
                    $orderQty   += $item->quantity;
                }

                $totalAmount = $order->{$amountCol} ?? $order->total_amount ?? $order->total_price ?? 0;
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
}