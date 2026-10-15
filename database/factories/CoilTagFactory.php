<?php

namespace Database\Factories;

use App\Models\CoilTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoilTag>
 */
class CoilTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor_coil' => fake()->unique()->bothify('HKG########'),
            'grade' => 'SNI 8784:2024 B',
            'tebal' => '1.90000000',
            'lebar' => '1195.00000000',
            'ukuran' => '1.9 × 1195',
            'berat' => '11.73000000',
            'company' => 'PTKP',
            'field_1' => '0',
            'field_6' => '0',
            'field_9' => '0',
            'tag_taehang' => fake()->unique()->numerify('##-###'),
            'lokasi_coil' => null,
            'user_id' => null,
            'raw_kp' => '0,HKG0215600008,SNI 8784:2024 B,1.9,1195,0,11.730,PTKP,0,',
            'raw_taehang' => '09-020',
            'scanned_at' => now(),
        ];
    }
}
