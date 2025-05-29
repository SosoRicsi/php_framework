<?php

declare(strict_types=1);

namespace Radiant\Collection;

use ArrayAccess;
use InvalidArgumentException;

/**
 * Class SmartCollection
 *
 * A flexible collection class implementing ArrayAccess, allowing array-like behavior with additional helper methods.
 */
final class SmartCollection implements ArrayAccess
{
    /**
     * @var array Holds the collection items.
     */
    private array $items = [];

    /**
     * Collection constructor.
     *
     * @param  array  $items  Optional initial array of items to populate the collection.
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->items[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->items[$key]);
    }

    /**
     * Magic getter to access items by key.
     *
     * @param  string|int  $key  The key to retrieve.
     * @return mixed The item associated with the given key.
     *
     * @throws InvalidArgumentException if the key does not exist.
     */
    public function __get(string|int $key): mixed
    {
        return $this->items[$key] ?? throw new InvalidArgumentException("Array key [{$key}] does not exist!");
    }

    /**
     * Adds an item to the collection.
     *
     * If a key is provided, the item will be stored at that key.
     * If no key is provided, the item will be appended to the end of the collection.
     *
     * @param  mixed  $value  The item to add to the collection.
     * @param  string|int|null  $key  Optional key to associate with the item.
     * @return $this The current instance for method chaining.
     */
    public function add(mixed $value, string|int|null $key = null): self
    {
        if (empty($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }

        return $this;
    }

    /**
     * Returns all items in the collection.
     *
     * @return array The collection of items.
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Sets an offset for ArrayAccess.
     *
     * @param  string|int|null  $offset  The offset to set.
     * @param  mixed  $value  The value to assign to the offset.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Checks if the specified offset exists.
     *
     * @param  string|int  $offset  The offset to check.
     * @return bool True if the offset exists, otherwise false.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * Unsets the specified offset.
     *
     * @param  string|int  $offset  The offset to unset.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Gets the value at the specified offset.
     *
     * @param  string|int  $offset  The offset to retrieve.
     * @return mixed|null The value at the offset, or null if it does not exist.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * Returns the number of items in the collection.
     *
     * @return int The count of items.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Retrieves the first item in the collection.
     *
     * @return mixed|null The first item, or null if the collection is empty.
     */
    public function first(): mixed
    {
        return reset($this->items) ?: null;
    }

    /**
     * Retrieves the last item in the collection.
     *
     * @return mixed|null The last item, or null if the collection is empty.
     */
    public function last(): mixed
    {
        return end($this->items) ?: null;
    }

    /**
     * Checks if the collection is not empty.
     *
     * @return bool True if not empty, otherwise false.
     */
    public function isNotEmpty(): bool
    {
        return ! empty($this->items);
    }

    /**
     * Merges an array with the collection's items.
     *
     * @param  array  $array  The array to merge.
     * @return $this The current instance for method chaining.
     */
    public function merge(array $array): self
    {
        $this->items = array_merge($this->items, $array);

        return $this;
    }

    /**
     * Collapses all nested arrays in the collection into a single-level array.
     *
     * @return $this The current instance for method chaining.
     */
    public function collapse(): self
    {
        $flatArray = [];
        $this->arrayBreakdown($this->items, $flatArray);
        $this->items = $flatArray;

        return $this;
    }

    /**
     * Filters the collection based on a callback.
     *
     * @param  callable  $callback  A callback function to determine if an item should remain in the collection.
     * @return static A new instance with the filtered items.
     */
    public function filter(callable $callback): static
    {
        return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Applies a callback to each item in the collection.
     *
     * @param  callable  $callback  A callback function to transform each item.
     * @return static A new instance with the transformed items.
     */
    public function map(callable $callback): static
    {
        return new static(array_map($callback, $this->items));
    }

    /**
     * Converts the collection to a JSON string.
     *
     * @param  int  $options  JSON encoding options (optional).
     * @return string The JSON representation of the collection.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->items, $options);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Splits the collection into chunks of a specified size.
     *
     * @param  int  $size  The size of each chunk.
     * @return static[] An array of new instances, each representing a chunk.
     */
    public function chunk(int $size): array
    {
        return array_map(fn ($chunk) => new static($chunk), array_chunk($this->items, $size));
    }

    /**
     * Recursively flattens nested arrays into a single-level array.
     *
     * @param  array  $array  The array to flatten.
     * @param  array  &$flatArray  The resulting flattened array.
     * @param  string  $prefix  Optional prefix for key generation in nested arrays.
     * @return $this The current instance for method chaining.
     */
    private function arrayBreakdown(array $array, array &$flatArray, string $prefix = ''): self
    {
        foreach ($array as $key => $value) {
            $newKey = $prefix.(is_int($key) ? "$key" : ".$key");

            if (is_array($value)) {
                $this->arrayBreakdown($value, $flatArray, $newKey);
            } else {
                $flatArray[$newKey] = $value;
            }
        }

        return $this;
    }
}
