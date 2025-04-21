<?php

namespace Domain\Builders;

use app\Domain\Models\OrderItem;
use Infrastructure\Database\IDbContext;
use Faker\Factory;

class OrderItemBuilder
{
    private array $data;
    private ?int $orderId = null;

    public function __construct()
    {
        $faker = Factory::create();
        $this->data = [
            'name' => $faker->words(2, true),
            'value' => $faker->randomFloat(2, 1, 100),
            'creation_date' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s')
        ];
    }

    public function value(float $value): static
    {
        $this->data['value'] = $value;
        return $this;
    }

    public function orderId(int $orderId): static
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function forOrder(int $orderId): static
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function createdAt(string $datetime): static
    {
        $this->data['creation_date'] = $datetime;
        return $this;
    }

    public function getValue(): float
    {
        return $this->data['value'];
    }

    public function getCreatedAt(): string
    {
        return $this->data['creation_date'];
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function build(): array
    {
        if (!$this->orderId) {
            throw new \Exception('Order ID must be set for OrderItem');
        }

        $this->data['order_id'] = $this->orderId;
        return $this->data;
    }

    public function create(IDbContext $db): int
    {
        $built = $this->build();
        return $db->table(OrderItem::tableName())->insert($built);
    }
}
