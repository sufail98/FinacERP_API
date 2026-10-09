<?php

namespace Database\Factories;

use App\Models\AccountGroup;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccountGroup>
 */
class AccountGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AccountGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'AccountGroupCode' => 'AG' . $this->faker->unique()->numberBetween(1000, 9999),
            'accountGroupName' => $this->faker->company() . ' Group',
            'groupUnder' => null,
            'narration' => $this->faker->sentence(),
            'LedgerNextNo' => $this->faker->numberBetween(1, 1000),
            'branchId' => Branch::factory(),
            'CreatedDate' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'CreatedUser' => $this->faker->uuid(),
            'ModifiedDate' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'ModifiedUser' => $this->faker->uuid(),
        ];
    }

    /**
     * Indicate that the account group is a child group.
     */
    public function asChildGroup(): static
    {
        return $this->state(fn (array $attributes) => [
            'groupUnder' => AccountGroup::factory(),
        ]);
    }

    /**
     * Indicate that the account group is a root group.
     */
    public function asRootGroup(): static
    {
        return $this->state(fn (array $attributes) => [
            'groupUnder' => null,
        ]);
    }
}
