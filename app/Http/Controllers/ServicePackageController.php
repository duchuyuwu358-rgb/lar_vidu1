<?php

namespace App\Http\Controllers;

use App\Models\ServicePackage;
use Illuminate\Http\Request;

class ServicePackageController extends Controller
{
    /**
     * Hiển thị danh sách gói dịch vụ kèm tìm kiếm & lọc
     */
    public function index(Request $request)
    {
        $query = ServicePackage::where('is_active', true);

        // 1. Tìm kiếm theo tên hoặc mô tả dịch vụ
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 2. Lọc theo khoảng giá
        if ($request->filled('price_range')) {
            switch ($request->input('price_range')) {
                case 'under_300':
                    $query->where('price', '<', 300000);
                    break;
                case '300_500':
                    $query->whereBetween('price', [300000, 500000]);
                    break;
                case 'above_500':
                    $query->where('price', '>', 500000);
                    break;
            }
        }

        // 3. Sắp xếp kết quả
        if ($request->filled('sort')) {
            switch ($request->input('sort')) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                default:
                    $query->latest();
                    break;
            }
        } else {
            $query->latest();
        }

        // Lấy danh sách phân trang (9 gói/trang) và giữ bộ lọc trên URL
        $services = $query->paginate(9)->withQueryString();

        return view('services.index', compact('services'));
    }

    /**
     * Thêm gói dịch vụ vào giỏ hàng
     */
    public function addToCart($id)
    {
        $service = ServicePackage::findOrFail($id);

        if (!$service->is_active) {
            return redirect()->back()->with('error', 'Gói dịch vụ này hiện tại tạm ngừng phục vụ!');
        }

        $cart = session()->get('cart', []);
        $cartKey = 'service_' . $service->id;

        // Nếu đã có trong giỏ thì tăng số lượng, chưa có thì thêm mới
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += 1;
        } else {
            $cart[$cartKey] = [
                'cart_key' => $cartKey,
                'id'       => $service->id,
                'name'     => $service->name,
                'price'    => $service->price,
                'quantity' => 1,
                'category' => 'Gói Dịch Vụ',
                'model'    => 'Dịch vụ',
                'color'    => null,
                'image'    => $service->image,
                'type'     => 'service',
            ];
        }

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Đã thêm gói dịch vụ vào giỏ hàng!');
    }
}