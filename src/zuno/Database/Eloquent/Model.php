<?php

namespace Zuno\Database\Eloquent;

use Zuno\Support\Collection;
use Zuno\Database\Eloquent\Query\QueryCollection;
use Zuno\Database\Contracts\Support\Jsonable;
use Stringable;
use JsonSerializable;
use ArrayAccess;

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
     * Indicates whether the model should maintain timestamps (`created_at` and `updated_at` fields.).
     *
     * @var bool
     */
    protected $timeStamps = true;

    /**
     * @var array
     * Holds the loaded relationships
     */
    protected $relations = [];

    /**
     * @var string
     * The last relation type that was set
     */
    protected $lastRelationType;

    /**
     * @var string
     * The last related model that was set
     */
    protected $lastRelatedModel;

    /**
     * @var string
     * The last foreign key that was set
     */
    protected $lastForeignKey;

    /**
     * @var string
     * The last local key that was set
     */
    protected $lastLocalKey;

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
        return strtolower($className);
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
     * Get the data except unexposed attributes
     * @param array $attributes
     * @return self
     */
    public function makeHidden(array $attributes): self
    {
        $this->unexposable = array_merge($this->unexposable, $attributes);

        return $this;
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

    /**
     * Delete the model from the database.
     *
     * @return bool True if the deletion was successful, false otherwise.
     */
    public function delete(): bool
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }

        return static::query()
            ->where($this->primaryKey, '=', $this->attributes[$this->primaryKey])
            ->delete();
    }

    /**
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     *
     * @return mixed
     */
    public function oneToOne(string $related, string $foreignKey, string $localKey)
    {
        $this->lastRelationType = 'oneToOne';
        $this->lastRelatedModel = $related;
        $this->lastForeignKey = $foreignKey;
        $this->lastLocalKey = $localKey;

        $relatedInstance = app($related);
        return $relatedInstance->query()->where($foreignKey, '=', $this->$localKey);
    }

    /**
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     * 
     * @return mixed
     */
    public function oneToMany(string $related, string $foreignKey, string $localKey)
    {
        $this->lastRelationType = 'oneToMany';
        $this->lastRelatedModel = $related;
        $this->lastForeignKey = $foreignKey;
        $this->lastLocalKey = $localKey;

        $relatedInstance = app($related);
        return $relatedInstance->query()->where($foreignKey, '=', $this->$localKey);
    }

    /**
     * Get the last relation type
     *
     * @return string
     */
    public function getLastRelationType(): string
    {
        return $this->lastRelationType;
    }

    /**
     * Get the last related model
     *
     * @return string
     */
    public function getLastRelatedModel(): string
    {
        return $this->lastRelatedModel;
    }

    /**
     * Get the last foreign key
     *
     * @return string
     */
    public function getLastForeignKey(): string
    {
        return $this->lastForeignKey;
    }

    /**
     * Get the last local key
     *
     * @return string
     */
    public function getLastLocalKey(): string
    {
        return $this->lastLocalKey;
    }

    /**
     * Set a relationship value
     *
     * @param string $relation
     * @param mixed $value
     */
    public function setRelation(string $relation, $value): self
    {
        $this->relations[$relation] = $value;
        return $this;
    }

    /**
     * Get a relationship value
     *
     * @param string $relation
     * @return mixed
     */
    public function getRelation(string $relation)
    {
        return $this->relations[$relation] ?? null;
    }

    /**
     * Magic getter for accessing model attributes and relationships
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        // Return relation if already loaded
        if (array_key_exists($name, $this->relations)) {
            return $this->relations[$name];
        }

        // Check if it's a relationship method
        if (method_exists($this, $name)) {
            $relation = $this->$name();

            // If it's a Builder instance (for eager loading or direct access)
            if ($relation instanceof Builder) {
                $relationType = $this->getLastRelationType();

                if ($relationType === 'oneToOne') {
                    $result = $relation->first();
                    $this->setRelation($name, $result);
                    return $result;
                } elseif ($relationType === 'oneToMany') {
                    $results = $relation->get();
                    $this->setRelation($name, $results);
                    return $results;
                }
            }

            return $relation;
        }

        return $this->attributes[$name] ?? null;
    }

    /**
     * Convert collection to array
     * @return array
     */
    public function toArray(): array
    {
        $attributes = $this->makeVisible();

        // Include loaded relationships
        foreach ($this->relations as $key => $relation) {
            $attributes[$key] = $relation instanceof Model
                ? $relation->toArray()
                : ($relation instanceof Collection ? $relation->toArray() : $relation);
        }

        return $attributes;
    }
}
