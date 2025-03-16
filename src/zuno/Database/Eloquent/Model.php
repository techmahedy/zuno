<?php

namespace Zuno\Database\Eloquent;

use Zuno\Contracts\Support\Jsonable;
use Stringable;
use JsonSerializable;
use ArrayAccess;
use Zuno\Database\Eloquent\Query\QueryCollection;

/**
 * The Model class serves as the base class for all Eloquent models.
 * It provides functionality for attribute management, JSON serialization,
 * and interaction with the database through the QueryCollection trait.
 */
abstract class Model implements ArrayAccess, JsonSerializable, Stringable, Jsonable
{
    use QueryCollection;

    /**
     * The name of the database table associated with the model.
     * If not set, it will be inferred from the class name.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model. Defaults to 'id'.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The model's attributes (key-value pairs).
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Attributes that are allowed to be mass-assigned.
     *
     * @var array
     */
    protected $creatable = [];

    /**
     * Attributes that should not be exposed when serializing the model.
     *
     * @var array
     */
    protected $unexposable = [];

    /**
     * The number of items to show per page for pagination.
     *
     * @var int
     */
    protected $pageSize = 15;

    /**
     * Model constructor.
     *
     * @param array $attributes Initial attributes to populate the model.
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);

        // Automatically set the table name
        // If not explicitly defined.
        if (!isset($this->table)) {
            $this->table = $this->getTableName();
        }
    }

    /**
     * Infers the table name from the class name.
     *
     * @return string The inferred table name.
     */
    protected function getTableName()
    {
        $className = get_class($this);
        $className = substr($className, strrpos($className, '\\') + 1);
        return strtolower($className) . 's';
    }

    /**
     * Mass-assign attributes to the model.
     *
     * @param array $attributes Key-value pairs of attributes to assign.
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $this->sanitize($value);
        }
    }

    /**
     * Sanitizes a value before assigning it to an attribute.
     * Override this method to implement custom sanitization logic.
     *
     * @param mixed $value The value to sanitize.
     * @return mixed The sanitized value.
     */
    protected function sanitize($value)
    {
        return $value;
    }

    /**
     * Magic getter for accessing model attributes.
     *
     * @param string $name The attribute name.
     * @return mixed The attribute value or null if it doesn't exist.
     */
    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Magic setter for assigning values to model attributes.
     *
     * @param string $name The attribute name.
     * @param mixed $value The value to assign.
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $this->sanitize($value);
    }

    /**
     * Returns an array of attributes that are not marked as unexposable.
     *
     * @return array The visible attributes.
     */
    public function makeVisible()
    {
        $visibleAttributes = [];
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->unexposable)) {
                $visibleAttributes[$key] = $value;
            }
        }
        return $visibleAttributes;
    }

    /**
     * Serializes the model to an array for JSON representation.
     *
     * @return array The array representation of the model.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Converts the model to a JSON string.
     *
     * @return string The JSON representation of the model.
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Checks if an attribute exists (ArrayAccess implementation).
     *
     * @param mixed $offset The attribute name.
     * @return bool True if the attribute exists, false otherwise.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Retrieves an attribute value (ArrayAccess implementation).
     *
     * @param mixed $offset The attribute name.
     * @return mixed The attribute value or null if it doesn't exist.
     */
    public function offsetGet($offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    /**
     * Sets an attribute value (ArrayAccess implementation).
     *
     * @param mixed $offset The attribute name.
     * @param mixed $value The value to assign.
     */
    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $this->sanitize($value);
    }

    /**
     * Unsets an attribute (ArrayAccess implementation).
     *
     * @param mixed $offset The attribute name.
     */
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }
}
