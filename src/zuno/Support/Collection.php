<?php

namespace Zuno\Support;

class Collection implements \ArrayAccess, \IteratorAggregate, \Countable
{
    protected $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function except(array $hidden): self
    {
        $filteredItems = array_map(function ($item) use ($hidden) {
            if (is_object($item)) {
                if (method_exists($item, 'toArray')) {
                    $item = $item->toArray();
                } else {
                    $item = get_object_vars($item);
                }
            }

            return array_diff_key($item, array_flip($hidden));
        }, $this->items);

        return new static($filteredItems);
    }

    public function first()
    {
        return $this->items[0] ?? null;
    }

    public function last()
    {
        return $this->items[count($this->items) - 1] ?? null;
    }

    public function map(callable $callback): self
    {
        return new static(array_map($callback, $this->items));
    }

    public function filter(callable $callback): self
    {
        return new static(array_filter($this->items, $callback));
    }

    public function pluck(string $key): array
    {
        return array_map(fn($item) => $item[$key] ?? null, $this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
