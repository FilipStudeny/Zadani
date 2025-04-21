<?php

namespace Domain\Builders;

use app\Domain\Models\Order;
use app\Domain\Enums\OrderStatus;
use Infrastructure\Database\IDbContext;
use Faker\Factory;

class OrderBuilder
{
    private array $data;

    public function __construct()
    {
        $faker = Factory::create();
        $this->data = [
            'name' => $faker->words(2, true),
            'amount_in_stock' => $faker->numberBetween(1, 1000),
            'price' => $faker->randomFloat(2, 10, 500),
            'date_of_creation' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
            'status' => $faker->randomElement(OrderStatus::cases())->value
        ];
    }

    public function name(string $name): static
    {
        $this->data['name'] = $name;
        return $this;
    }

    public function stock(int $amount): static
    {
        $this->data['amount_in_stock'] = $amount;
        return $this;
    }

    public function price(float $price): static
    {
        $this->data['price'] = $price;
        return $this;
    }

    public function createdAt(string $datetime): static
    {
        $this->data['date_of_creation'] = $datetime;
        return $this;
    }

    public function status(OrderStatus $status): static
    {
        $this->data['status'] = $status->value;
        return $this;
    }

    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getStock(): int
    {
        return $this->data['amount_in_stock'];
    }

    public function getPrice(): float
    {
        return $this->data['price'];
    }

    public function getCreatedAt(): string
    {
        return $this->data['date_of_creation'];
    }

    public function getStatus(): string
    {
        return $this->data['status'];
    }

    public function build(): array
    {
        return $this->data;
    }

    public function create(IDbContext $db): int
    {
        return $db->table(Order::tableName())->insert($this->data);
    }
}
