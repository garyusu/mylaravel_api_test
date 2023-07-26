<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @return string
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        //faker 用於產生隨機內容
        $type = $this->faker->randomElement(['I', 'B']);
        $name = $type =='I' ? $this->faker->name() : $this->faker->company(); 

        return [
            'name' => $name,
            'type' => $type,                
            'email' => $this->faker->email(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),   //州別
            'postal_code' => $this->faker->postCode()
        ];
    }
}
