<?php

namespace Zuno\Database\Eloquent\Query;

use Carbon\Carbon;
use Zuno\Database\Eloquent\Builder;
use Zuno\Database\Database;
use Zuno\Support\Collection;

/**
 * The QueryCollection trait provides methods for querying the database
 * and interacting with model collections. It is designed to be used
 * within Eloquent models to enable fluent query building and data retrieval.
 */
trait QueryCollection
{
    /**
     * Creates and returns a new query builder instance for the model.
     *
     * @return Builder A new query builder instance.
     */
    public static function query(): Builder
    {
        $model = new static();

        return new Builder(
            Database::getPdoInstance(),
            $model->table,
            get_class($model),
            $model->pageSize
        );
    }

    /**
     * Retrieves all records from the model's table.
     *
     * @return Collection A collection of all model records.
     */
    public static function all(): Collection
    {
        return static::query()->get();
    }

    /**
     * Alias for the `all` method. Retrieves all records from the model's table.
     *
     * @return Collection A collection of all model records.
     */
    public static function get(): Collection
    {
        return static::all();
    }

    /**
     * Finds a model record by its primary key.
     *
     * @param mixed $primaryKey The value of the primary key to search for.
     * @return mixed The model instance or null if no record is found.
     */
    public static function find(mixed $primaryKey)
    {
        return static::query()
            ->where((new static)->primaryKey, '=', $primaryKey)
            ->first();
    }

    /**
     * Returns the total number of records in the model's table.
     *
     * @return int The count of records.
     */
    public static function count(): int
    {
        return static::query()->count();
    }

    /**
     * Converts the model's attributes to an array.
     *
     * @return array The array representation of the model's visible attributes.
     */
    public function toArray(): array
    {
        return $this->makeVisible();
    }

    /**
     * Converts the model's attributes to a JSON string.
     *
     * @param int $options Bitmask of JSON encoding options.
     * @return string The JSON representation of the model.
     * @throws \Exception If JSON encoding fails.
     */
    public function toJson($options = 0): string
    {
        try {
            $json = json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $json;
    }

    /**
     * Save the model to the database.
     *
     * @return bool
     */
    public function save(): bool
    {
        $attributes = $this->getCreatableAttributes();

        if (isset($this->attributes[$this->primaryKey])) {
            if ($this->timeStamps) {
                $attributes['updated_at'] = Carbon::now();
            }

            $primaryKeyValue = $this->attributes[$this->primaryKey];
            return $this->query()
                ->where($this->primaryKey, '=', $primaryKeyValue)
                ->update($attributes);
        }

        if ($this->timeStamps) {
            $attributes['created_at'] = Carbon::now();
            $attributes['updated_at'] = Carbon::now();
        }

        $id = $this->query()->insert($attributes);
        if ($id) {
            $this->attributes[$this->primaryKey] = $id;
            return true;
        }

        return false;
    }

    /**
     * Create a new model instance and save it to the database.
     *
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes): static
    {
        $model = new static();
        $model->fill($attributes);
        $model->save();
        return $model;
    }

    /**
     * Get the attributes that are allowed to be mass-assigned.
     *
     * @return array
     */
    protected function getCreatableAttributes(): array
    {
        $creatableAttributes = [];
        foreach ($this->creatable as $attribute) {
            if (isset($this->attributes[$attribute])) {
                $creatableAttributes[$attribute] = $this->attributes[$attribute];
            }
        }
        return $creatableAttributes;
    }
}
