<?php

namespace Database\Seeders;

use App\Models\Hood;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();

        if ($categories->isEmpty()) {
            $this->call(CategorySeeder::class);
            $categories = Category::all();
        }

        $hoods = [
            [
                'name' => 'XFAN 800 Premium Wall Mount',
                'model' => 'XF-800-WM',
                'description' => 'Máy hút mùi treo tường cao cấp với công suất 800W, thiết kế hiện đại',
                'category_id' => $categories->first()->id,
                'price' => 3500000,
                'power' => '800W',
                'dimensions' => '80x60x35cm',
                'color' => 'Trắng',
                'manufacturer' => 'XFAN',
                'material' => 'Inox',
                'warranty_months' => 24,
                'type' => 'wall-mounted',
                'is_active' => true,
                'stock_quantity' => 15,
            ],
            [
                'name' => 'XFAN 600 Under Cabinet',
                'model' => 'XF-600-UC',
                'description' => 'Máy hút mùi gắn dưới tủ bếp với thiết kế gọn nhẹ',
                'category_id' => $categories[1]->id ?? $categories->first()->id,
                'price' => 2500000,
                'power' => '600W',
                'dimensions' => '70x50x25cm',
                'color' => 'Đen',
                'manufacturer' => 'XFAN',
                'material' => 'Nhựa ABS + Inox',
                'warranty_months' => 12,
                'type' => 'under-cabinet',
                'is_active' => true,
                'stock_quantity' => 8,
            ],
            [
                'name' => 'XFAN 1000 Island Luxury',
                'model' => 'XF-1000-IS',
                'description' => 'Máy hút mùi kiểu đảo cao cấp cho bếp hiện đại, công suất mạnh 1000W',
                'category_id' => $categories[2]->id ?? $categories->first()->id,
                'price' => 5500000,
                'power' => '1000W',
                'dimensions' => '100x80x40cm',
                'color' => 'Inox',
                'manufacturer' => 'XFAN',
                'material' => 'Inox cao cấp',
                'warranty_months' => 36,
                'type' => 'island',
                'is_active' => true,
                'stock_quantity' => 5,
            ],
            [
                'name' => 'XFAN 700 Glass Edition',
                'model' => 'XF-700-GE',
                'description' => 'Máy hút mùi với mặt kính cường lực tạo nét sang trọng',
                'category_id' => $categories[3]->id ?? $categories->first()->id,
                'price' => 4200000,
                'power' => '700W',
                'dimensions' => '75x65x30cm',
                'color' => 'Đen + Kính',
                'manufacturer' => 'XFAN',
                'material' => 'Kính cường lực + Inox',
                'warranty_months' => 24,
                'type' => 'wall-mounted',
                'is_active' => true,
                'stock_quantity' => 12,
            ],
            [
                'name' => 'XFAN 500 Basic',
                'model' => 'XF-500-BS',
                'description' => 'Máy hút mùi cơ bản, giá rẻ nhưng chất lượng tốt',
                'category_id' => $categories[4]->id ?? $categories->first()->id,
                'price' => 1800000,
                'power' => '500W',
                'dimensions' => '60x45x20cm',
                'color' => 'Trắng',
                'manufacturer' => 'XFAN',
                'material' => 'Nhựa',
                'warranty_months' => 12,
                'type' => 'under-cabinet',
                'is_active' => true,
                'stock_quantity' => 20,
            ],
            [
                'name' => 'XFAN 900 Professional',
                'model' => 'XF-900-PRO',
                'description' => 'Máy hút mùi chuyên nghiệp cho bếp thương mại hoặc nhà có diện tích lớn',
                'category_id' => $categories->first()->id,
                'price' => 6800000,
                'power' => '900W',
                'dimensions' => '90x75x40cm',
                'color' => 'Inox',
                'manufacturer' => 'XFAN',
                'material' => 'Inox công nghiệp',
                'warranty_months' => 36,
                'type' => 'wall-mounted',
                'is_active' => true,
                'stock_quantity' => 3,
            ],
        ];

        foreach ($hoods as $hood) {
            Hood::create($hood);
        }
    }
}
