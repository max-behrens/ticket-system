<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition()
    {
        // ~0.01% chance of a ticket being a winner.
        $isWinner = mt_rand(1, 10000) === 1;


        // Assign random prize values only if winner
        $prizeValue = $isWinner ? $this->faker->randomElement([1.00, 5.00, 10.00, 25.00, 100.00]) : 0;

        return [
            'code' => strtoupper($this->faker->unique()->lexify('Ticket-???????')),
            'prize_value' => $prizeValue,
            'is_winner' => $isWinner,
            'is_sold' => false,
        ];
    }
}