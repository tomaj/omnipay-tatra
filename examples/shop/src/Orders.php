<?php

declare(strict_types=1);

namespace Shop;

/**
 * The shop's orders, kept in a JSON file so that the example needs no database.
 */
final class Orders
{
    public function __construct(private readonly string $file) {}

    /**
     * @return array{vs: string, amount: string, currency: string, status: string}
     */
    public function create(string $amount): array
    {
        $orders = $this->all();
        $vs = (string) (2026100 + count($orders) + 1);
        $orders[$vs] = ['vs' => $vs, 'amount' => $amount, 'currency' => 'EUR', 'status' => 'new'];
        $this->save($orders);

        return $orders[$vs];
    }

    /**
     * @return array{vs: string, amount: string, currency: string, status: string}|null
     */
    public function find(string $vs): ?array
    {
        return $this->all()[$vs] ?? null;
    }

    public function setStatus(string $vs, string $status): void
    {
        $orders = $this->all();
        $orders[$vs]['status'] = $status;
        $this->save($orders);
    }

    /**
     * @return array<string, array{vs: string, amount: string, currency: string, status: string}>
     */
    private function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }

        /** @var array<string, array{vs: string, amount: string, currency: string, status: string}> */
        return json_decode((string) file_get_contents($this->file), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, array{vs: string, amount: string, currency: string, status: string}> $orders
     */
    private function save(array $orders): void
    {
        file_put_contents($this->file, json_encode($orders, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX);
    }
}
