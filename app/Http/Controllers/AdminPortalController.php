<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Hood;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPortalController extends Controller
{
    private function getTotalColumn()
    {
        if (Schema::hasColumn('orders', 'total_price')) return 'total_price';
        if (Schema::hasColumn('orders', 'total_amount')) return 'total_amount';
        if (Schema::hasColumn('orders', 'total')) return 'total';
        return 'grand_total';
    }

    private function getProductFkColumn()
    {
        if (Schema::hasColumn('order_items', 'hood_id')) return 'hood_id';
        if (Schema::hasColumn('order_items', 'product_id')) return 'product_id';
        return null;
    }

    private function getCompletedStatuses()
    {
        return [
            'paid', 'PAID', 'completed', 'COMPLETED', 
            'success', 'SUCCESS', 'processing', 'PROCESSING',
            'ĐÃ THANH TOÁN', 'THÀNH CÔNG', 'đã thanh toán', 'thành công'
        ];
    }

    private function getFailedStatuses()
    {
        return [
            'failed', 'FAILED', 'cancelled', 'CANCELLED', 'cancel',
            'THẤT BẠI / ĐÃ HỦY', 'THẤT BẠI', 'ĐÃ HỦY', 'thất bại', 'đã hủy', 'hủy'
        ];
    }

    public function index(Request $request)
    {
        $period = $request->get('period', '1week');
        $totalColumn = $this->getTotalColumn();
        $productFk = $this->getProductFkColumn();
        $hasPaymentStatus = Schema::hasColumn('orders', 'payment_status');
        $completedStatuses = $this->getCompletedStatuses();
        $failedStatuses = $this->getFailedStatuses();

        $filterCompletedOrders = function ($q) use ($completedStatuses, $hasPaymentStatus) {
            $q->where(function ($sub) use ($completedStatuses, $hasPaymentStatus) {
                $sub->whereIn('status', $completedStatuses);
                if ($hasPaymentStatus) {
                    $sub->orWhereIn('payment_status', $completedStatuses);
                }
            });
        };

        // 1. Thống kê tổng quan
        $totalProducts   = Hood::count();
        $totalCategories = Category::count();
        $totalCustomers  = User::whereNotIn('role', ['admin'])->count();
        $totalSold       = OrderItem::whereHas('order', $filterCompletedOrders)->sum('quantity');
        $totalRevenue    = Order::where($filterCompletedOrders)->sum($totalColumn) ?? 0;

        // 2. Dữ liệu biểu đồ theo mốc thời gian
        $chartLabels = [];
        $chartValues = [];
        $chartTitle  = '';

        switch ($period) {
            case '1day':
                $chartTitle = 'Biểu đồ doanh thu hôm nay (24 giờ)';
                $todayStart = now()->startOfDay();
                $salesData = Order::where($filterCompletedOrders)
                    ->where('created_at', '>=', $todayStart)
                    ->select(DB::raw('HOUR(created_at) as hour_key'), DB::raw("SUM({$totalColumn}) as total"))
                    ->groupBy(DB::raw('HOUR(created_at)'))->pluck('total', 'hour_key')->toArray();

                for ($h = 0; $h < 24; $h++) {
                    $chartLabels[] = sprintf('%02d:00', $h);
                    $chartValues[] = (float) ($salesData[$h] ?? 0);
                }
                break;

            case '1month':
                $chartTitle = 'Biểu đồ doanh thu 30 ngày gần nhất';
                $startDate = now()->subDays(29)->startOfDay();
                $salesData = Order::where($filterCompletedOrders)
                    ->where('created_at', '>=', $startDate)
                    ->select(DB::raw('DATE(created_at) as date_key'), DB::raw("SUM({$totalColumn}) as total"))
                    ->groupBy(DB::raw('DATE(created_at)'))->pluck('total', 'date_key')->toArray();

                for ($i = 29; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $chartLabels[] = $date->format('d/m');
                    $chartValues[] = (float) ($salesData[$date->format('Y-m-d')] ?? 0);
                }
                break;

            case '1year':
                $chartTitle = 'Biểu đồ doanh thu 12 tháng gần nhất';
                $startDate = now()->subMonths(11)->startOfMonth();
                $salesData = Order::where($filterCompletedOrders)
                    ->where('created_at', '>=', $startDate)
                    ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month_key"), DB::raw("SUM({$totalColumn}) as total"))
                    ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))->pluck('total', 'month_key')->toArray();

                for ($m = 11; $m >= 0; $m--) {
                    $date = now()->subMonths($m);
                    $chartLabels[] = 'Thg ' . $date->format('m/Y');
                    $chartValues[] = (float) ($salesData[$date->format('Y-m')] ?? 0);
                }
                break;

            case 'all':
                $chartTitle = 'Biểu đồ doanh thu toàn thời gian';
                $salesData = Order::where($filterCompletedOrders)
                    ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month_key"), DB::raw("SUM({$totalColumn}) as total"))
                    ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
                    ->orderBy('month_key', 'ASC')->pluck('total', 'month_key')->toArray();

                $firstOrder = Order::min('created_at');
                $curr = $firstOrder ? \Carbon\Carbon::parse($firstOrder)->startOfMonth() : now()->startOfMonth();
                $endDate = now()->startOfMonth();
                while ($curr->lte($endDate)) {
                    $chartLabels[] = 'Thg ' . $curr->format('m/Y');
                    $chartValues[] = (float) ($salesData[$curr->format('Y-m')] ?? 0);
                    $curr->addMonth();
                }
                break;

            case '1week':
            default:
                $chartTitle = 'Biểu đồ doanh thu 7 ngày gần nhất';
                $startDate = now()->subDays(6)->startOfDay();
                $salesData = Order::where($filterCompletedOrders)
                    ->where('created_at', '>=', $startDate)
                    ->select(DB::raw('DATE(created_at) as date_key'), DB::raw("SUM({$totalColumn}) as total"))
                    ->groupBy(DB::raw('DATE(created_at)'))->pluck('total', 'date_key')->toArray();

                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i);
                    $chartLabels[] = $date->format('d/m');
                    $chartValues[] = (float) ($salesData[$date->format('Y-m-d')] ?? 0);
                }
                break;
        }

        // 3. Top 5 sản phẩm bán chạy nhất
        $topProducts = collect();
        if ($productFk) {
            try {
                $query = OrderItem::select($productFk, DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(price * quantity) as total_amount'))
                    ->groupBy($productFk)
                    ->orderByDesc('total_qty')
                    ->take(5);

                if (method_exists(OrderItem::class, 'hood')) {
                    $query->with('hood');
                } elseif (method_exists(OrderItem::class, 'product')) {
                    $query->with('product');
                }

                $topProducts = $query->get();
            } catch (\Exception $e) {
                $topProducts = collect();
            }
        }

        // 4. Thống kê đơn hàng theo trạng thái (Đã sửa logic đếm chuẩn xác)
        $totalOrdersCount = Order::count();
        $paidOrdersCount  = Order::where($filterCompletedOrders)->count();
        
        // Truy vấn trực tiếp đơn Hủy / Thất bại
        $failedOrdersCount = Order::where(function($q) use ($failedStatuses, $hasPaymentStatus) {
            $q->whereIn('status', $failedStatuses)
              ->orWhere('status', 'like', '%thất bại%')
              ->orWhere('status', 'like', '%hủy%');
            if ($hasPaymentStatus) {
                $q->orWhereIn('payment_status', $failedStatuses)
                  ->orWhere('payment_status', 'like', '%thất bại%')
                  ->orWhere('payment_status', 'like', '%hủy%');
            }
        })->count();

        // Đơn chờ thanh toán là phần còn lại
        $pendingOrdersCount = max(0, $totalOrdersCount - $paidOrdersCount - $failedOrdersCount);

        $recentOrders = Order::with('user')->latest()->take(5)->get();

        return view('admin.portal', compact(
            'totalProducts', 'totalCategories', 'totalCustomers', 
            'totalSold', 'totalRevenue', 'chartLabels', 'chartValues', 
            'chartTitle', 'period', 'topProducts', 'totalOrdersCount',
            'paidOrdersCount', 'pendingOrdersCount', 'failedOrdersCount', 'recentOrders'
        ));
    }

    // CHỨC NĂNG XUẤT FILE EXCEL
    public function exportExcel(Request $request)
    {
        $period = $request->get('period', 'all');
        $totalColumn = $this->getTotalColumn();

        $query = Order::with('user')->latest();

        if ($period == '1day') {
            $query->where('created_at', '>=', now()->startOfDay());
        } elseif ($period == '1week') {
            $query->where('created_at', '>=', now()->subDays(6)->startOfDay());
        } elseif ($period == '1month') {
            $query->where('created_at', '>=', now()->subDays(29)->startOfDay());
        } elseif ($period == '1year') {
            $query->where('created_at', '>=', now()->subMonths(11)->startOfMonth());
        }

        $orders = $query->get();

        $fileName = 'Bao_Cao_Doanh_Thu_' . date('Y_m_d_H_i') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Mã Đơn', 'Khách Hàng', 'Email', 'Tổng Tiền (VNĐ)', 'Trạng Thái Thanh Toán', 'Trạng Thái Đơn', 'Ngày Tạo'];

        $callback = function() use($orders, $columns, $totalColumn) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($file, $columns);

            foreach ($orders as $order) {
                $amount = $order->{$totalColumn} ?? 0;
                fputcsv($file, [
                    '#' . $order->id,
                    $order->user->name ?? 'Khách vãng lai',
                    $order->user->email ?? 'N/A',
                    $amount,
                    $order->payment_status ?? 'Chờ thanh toán',
                    $order->status ?? 'Mới',
                    $order->created_at ? $order->created_at->format('H:i d/m/Y') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}