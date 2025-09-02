<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition()
    {
        // 0.2% chance of havinga winning ticket code.
        $isWinner = $this->faker->numberBetween(1, 1000) <= (1000 * 0.002);
        // Have random prize values fo winning tickets.
        $prizeValue = $isWinner ? $this->faker->randomElement([1.00, 5.00, 10.00, 25.00, 100.00]) : 0;

        return [
            'code' => strtoupper($this->faker->unique()->lexify('Ticket-?????')),
            'prize_value' => $prizeValue,
            'is_winner' => $isWinner,
            'is_sold' => false,
        ];
    }
}