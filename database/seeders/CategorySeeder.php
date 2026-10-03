<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Máy Hút Mùi Dạng Tường', 'slug' => 'may-hut-mui-tang-tuong', 'description' => 'Máy hút mùi được lắp đặt trên tường bếp'],
            ['name' => 'Máy Hút Mùi Gắn Dưới Tủ', 'slug' => 'may-hut-mui-gan-duoi-tu', 'description' => 'Máy hút mùi gắn dưới tủ bếp'],
            ['name' => 'Máy Hút Mùi Dạng Đảo', 'slug' => 'may-hut-mui-dang-dao', 'description' => 'Máy hút mùi kiểu đảo cho bếp hiện đại'],
            ['name' => 'Máy Hút Mùi Kính', 'slug' => 'may-hut-mui-kinh', 'description' => 'Máy hút mùi bằng kính cường lực tạo thẩm mỹ'],
            ['name' => 'Máy Hút Mùi Inox', 'slug' => 'may-hut-mui-inox', 'description' => 'Máy hút mùi chế tác từ inox cao cấp'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
