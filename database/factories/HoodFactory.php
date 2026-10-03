<?php

namespace Database\Factories;

use App\Models\Hood;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hood>
 */
class HoodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'model' => $this->faker->bothify('MODEL-####'),
            'description' => $this->faker->paragraph(),
            'category_id' => Category::factory(),
            'price' => $this->faker->randomFloat(2, 500, 5000),
            'power' => $this->faker->randomElement(['500W', '600W', '700W', '800W', '900W', '1000W']),
            'dimensions' => $this->faker->randomElement(['60x50x25cm', '70x60x30cm', '80x70x35cm', '90x80x40cm']),
            'color' => $this->faker->randomElement(['Đen', 'Trắng', 'Inox', 'Bạc', 'Xám']),
            'manufacturer' => $this->faker->company(),
            'material' => $this->faker->randomElement(['Thép không gỉ', 'Hợp kim nhôm', 'Kính cường lực', 'Nhựa ABS']),
            'warranty_months' => $this->faker->numberBetween(12, 60),
            'type' => $this->faker->randomElement(['wall-mounted', 'under-cabinet', 'island', 'cooktop']),
            'is_active' => $this->faker->boolean(80),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
        ];
    }
}
