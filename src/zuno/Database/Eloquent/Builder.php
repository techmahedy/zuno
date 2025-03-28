<?php

namespace Zuno\Database\Eloquent;

use Zuno\Support\Collection;
use PDO;
use PDOException;
use PDOStatement;
use Zuno\Database\Eloquent\Query\QueryCollection;
use Zuno\Support\Facades\URL;

class Builder
{
    use QueryCollection;

    /**
     * @var PDO
     * Holds the PDO instance for database connectivity.
     */
    protected PDO $pdo;

    /**
     * @var string
     * The name of the database table to query.
     */
    protected string $table;

    /**
     * @var array
     * The fields to select in the query. Defaults to ['*'] which selects all columns.
     */
    protected array $fields = ['*'];

    /**
     * @var array
     * The conditions (WHERE clauses) to apply to the query.
     */
    protected array $conditions = [];

    /**
     * @var array
     * The ORDER BY clauses to sort the query results.
     */
    protected array $orderBy = [];

    /**
     * @var array
     * The GROUP BY clauses to group the query results.
     */
    protected array $groupBy = [];

    /**
     * @var int|null
     * The maximum number of records to return. Null means no limit.
     */
    protected ?int $limit = null;

    /**
     * @var int|null
     * The number of records to skip before starting to return records. Null means no offset.
     */
    protected ?int $offset = null;

    /**
     * @var string
     * The class name of the model associated with this query.
     */
    protected string $modelClass;

    /**
     * @var int
     * The number of rows to display per page for pagination.
     */
    protected int $rowPerPage;

    /**
     * @var array
     * Holds the relationships to be eager loaded
     */
    protected array $eagerLoad = [];

    /**
     * @param PDO $pdo
     * @param string $table
     * @param string $modelClass
     * @param int $rowPerPage
     */
    public function __construct(PDO $pdo, string $table, string $modelClass, int $rowPerPage)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->modelClass = $modelClass;
        $this->rowPerPage = $rowPerPage;
    }

    /**
     * Set the fields to select.
     *
     * @param array $fields
     * @return self
     */
    public function select(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Add a WHERE condition.
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return self
     */
    public function where(string $field, string $operator, $value): self
    {
        $this->conditions[] = ['AND', $field, $operator, $value];
        return $this;
    }

    /**
     * Add an OR WHERE condition.
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return self
     */
    public function orWhere(string $field, string $operator, $value): self
    {
        $this->conditions[] = ['OR', $field, $operator, $value];
        return $this;
    }

    /**
     * Add an ORDER BY clause.
     *
     * @param string $field
     * @param string $direction
     * @return self
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [$field, $direction];
        return $this;
    }

    /**
     * Add a GROUP BY clause.
     *
     * @param string $field
     * @return self
     */
    public function groupBy(string $field): self
    {
        $this->groupBy[] = $field;
        return $this;
    }

    /**
     * Set the LIMIT clause.
     *
     * @param int $limit
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the OFFSET clause.
     *
     * @param int $offset
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Generate the SQL query string.
     *
     * @return string
     */
    public function toSql(): string
    {

        $sql = 'SELECT ';

        // Handle SELECT clause
        if (!empty($this->groupBy)) {
            // If GROUP BY is used, ensure all selected fields are either aggregated or in the GROUP BY clause
            $groupedFields = $this->groupBy;
            $nonGroupedFields = array_diff($this->fields, $groupedFields);

            if (in_array('*', $this->fields)) {
                // If '*' is used, replace it with all table columns
                $this->fields = $this->getTableColumns();
                $nonGroupedFields = array_diff($this->fields, $groupedFields);
            }

            if (!empty($nonGroupedFields)) {
                // Aggregate non-grouped fields
                $aggregatedFields = array_map(fn($field) => "MAX($field) AS $field", $nonGroupedFields);
                $sql .= implode(', ', array_merge($groupedFields, $aggregatedFields));
            } else {
                $sql .= implode(', ', $groupedFields);
            }
        } else {
            // If no GROUP BY, use the original SELECT fields
            $sql .= implode(', ', $this->fields);
        }

        $sql .= ' FROM ' . $this->table;

        // Handle WHERE clause
        if (!empty($this->conditions)) {
            $conditionStrings = [];
            foreach ($this->conditions as $condition) {
                if ($condition[2] === 'IN') {
                    // Handle IN condition specially
                    $conditionStrings[] = "{$condition[1]} {$condition[2]} {$condition[4]}";
                } else {
                    $conditionStrings[] = "{$condition[1]} {$condition[2]} ?";
                }
            }
            $sql .= ' WHERE ' . implode(' ', $this->formatConditions($conditionStrings));
        }

        // Handle GROUP BY clause
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        // Handle ORDER BY clause
        if (!empty($this->orderBy)) {
            $orderByStrings = array_map(fn($o) => "$o[0] $o[1]", $this->orderBy);
            $sql .= ' ORDER BY ' . implode(', ', $orderByStrings);
        }

        // Handle LIMIT clause
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        // Handle OFFSET clause
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    /**
     * Get the list of columns for the table.
     *
     * @return array
     */
    protected function getTableColumns(): array
    {
        $stmt = $this->pdo->query("DESCRIBE {$this->table}");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $columns;
    }

    /**
     * Format conditions with AND/OR.
     *
     * @param array $conditionStrings
     * @return array
     */
    protected function formatConditions(array $conditionStrings): array
    {
        $formattedConditions = [];

        foreach ($this->conditions as $index => $condition) {
            if ($index > 0) {
                $formattedConditions[] = $condition[0];
            }
            $formattedConditions[] = $conditionStrings[$index];
        }

        return $formattedConditions;
    }

    /**
     * Execute the query and return a collection of models.
     *
     * @return Collection
     * @throws PDOException
     */
    public function get(): Collection
    {
        try {
            $stmt = $this->pdo->prepare($this->toSql());
            $this->bindValues($stmt);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $models = array_map(fn($item) => new $this->modelClass($item), $results);

            $collection = new Collection($this->modelClass, $models);

            // Eager load relationships if any
            if (!empty($this->eagerLoad)) {
                $this->eagerLoadRelations($collection);
            }

            return $collection;
        } catch (PDOException $e) {
            throw new PDOException("Database error: " . $e->getMessage());
        }
    }

    /**
     * Eager load the relationships for the collection
     *
     * @param Collection $collection
     * @return void
     */
    protected function eagerLoadRelations(Collection $collection)
    {
        foreach ($this->eagerLoad as $relation => $constraint) {
            $models = $collection->all();
            $firstModel = $models[0] ?? null;

            if (!$firstModel || !method_exists($firstModel, $relation)) {
                continue;
            }

            // Initialize relationship to get metadata
            $firstModel->$relation();
            $relationType = $firstModel->getLastRelationType();
            $relatedModel = $firstModel->getLastRelatedModel();
            $foreignKey = $firstModel->getLastForeignKey();
            $localKey = $firstModel->getLastLocalKey();

            // Gather all keys to load
            $keys = array_unique(array_filter(array_map(
                fn($model) => $model->$localKey,
                $models
            )));

            if (empty($keys)) {
                foreach ($models as $model) {
                    $model->setRelation(
                        $relation,
                        $relationType === 'oneToOne' ? null : new Collection($relatedModel, [])
                    );
                }
                continue;
            }

            // Create fresh query
            $query = (new $relatedModel)->query()
                ->whereIn($foreignKey, $keys);

            if (is_callable($constraint)) {
                $constraint($query);
            }

            // Get results as array of models (not Collection)
            $results = $query->get()->all(); // Changed from toArray()
            $grouped = [];

            foreach ($results as $result) {
                // Access attribute properly whether array or object
                $key = is_array($result)
                    ? $result[$foreignKey]
                    : $result->$foreignKey;

                if ($relationType === 'oneToOne') {
                    $grouped[$key] = $result;
                } else {
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [];
                    }
                    $grouped[$key][] = $result;
                }
            }

            // Attach relations to each model
            foreach ($models as $model) {
                $key = $model->$localKey;
                $model->setRelation(
                    $relation,
                    isset($grouped[$key])
                        ? ($relationType === 'oneToOne'
                            ? $grouped[$key]
                            : new Collection($relatedModel, $grouped[$key]))
                        : ($relationType === 'oneToOne'
                            ? null
                            : new Collection($relatedModel, []))
                );
            }
        }
    }

    public function getQuery()
    {
        return $this;
    }

    // Add this to Builder.php
    public function getModel()
    {
        return app($this->modelClass);
    }

    /**
     * Add a WHERE IN condition
     *
     * @param string $field
     * @param array $values
     * @return self
     */
    public function whereIn(string $field, array $values): self
    {
        if (empty($values)) {
            // If no values provided, make sure no results are returned
            $this->where($field, '=', 'NULL');
            return $this;
        }

        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->conditions[] = ['AND', $field, 'IN', $values, "($placeholders)"];
        return $this;
    }

    /**
     * Add a OR WHERE IN condition
     *
     * @param string $field
     * @param array $values
     * @return self
     */
    public function orWhereIn(string $field, array $values): self
    {
        if (empty($values)) {
            // If no values provided, make sure no results are returned
            $this->orWhere($field, '=', 'NULL');
            return $this;
        }

        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->conditions[] = ['OR', $field, 'IN', $values, "($placeholders)"];
        return $this;
    }

    /**
     * Load a specific relation for the collection
     *
     * @param Collection $collection
     * @param string $relation
     * @return void
     */
    protected function loadRelation(Collection $collection, string $relation, $constraint = null)
    {
        $models = $collection->all();
        $firstModel = $models[0] ?? null;

        if (!$firstModel || !method_exists($firstModel, $relation)) {
            return;
        }

        // Initialize the relationship to get metadata
        $firstModel->$relation();

        $relationType = $firstModel->getLastRelationType();
        $relatedModel = $firstModel->getLastRelatedModel();
        $foreignKey = $firstModel->getLastForeignKey();
        $localKey = $firstModel->getLastLocalKey();

        // Get all keys we need to load
        $keys = array_map(fn($model) => $model->$localKey, $models);
        $keys = array_unique($keys);

        // Create query for related models
        $query = (new $relatedModel)->query()
            ->whereIn($foreignKey, $keys);

        // Apply constraints if provided
        if (is_callable($constraint)) {
            $constraint($query);
        }

        // Get results and organize them
        $results = $query->get();

        $grouped = [];
        foreach ($results as $result) {
            if ($relationType === 'oneToOne') {
                $grouped[$result->$foreignKey] = $result;
            } else {
                $grouped[$result->$foreignKey][] = $result;
            }
        }

        // Assign relations to each model
        foreach ($models as $model) {
            $key = $model->$localKey;
            if (isset($grouped[$key])) {
                $model->setRelation(
                    $relation,
                    $relationType === 'oneToOne'
                        ? $grouped[$key]
                        : new Collection($relatedModel, $grouped[$key])
                );
            }
        }
    }

    /**
     * Load one-to-one relationships
     *
     * @param array $models
     * @param string $relation
     * @param string $relatedModel
     * @param string $foreignKey
     * @param string $localKey
     * @return void
     */
    protected function loadOneToOne(array $models, string $relation, string $relatedModel, string $foreignKey, string $localKey): void
    {
        $localKeys = array_map(fn($model) => $model->$localKey, $models);
        $relatedModels = $relatedModel::query()
            ->whereIn($foreignKey, $localKeys)
            ->get()
            ->keyBy($foreignKey);

        foreach ($models as $model) {
            $key = $model->$localKey;
            if (isset($relatedModels[$key])) {
                $model->setRelation($relation, $relatedModels[$key]);
            }
        }
    }

    /**
     * Load one-to-many relationships
     *
     * @param array $models
     * @param string $relation
     * @param string $relatedModel
     * @param string $foreignKey
     * @param string $localKey
     * @return void
     */
    protected function loadOneToMany(array $models, string $relation, string $relatedModel, string $foreignKey, string $localKey): void
    {
        $localKeys = array_map(fn($model) => $model->$localKey, $models);
        $relatedModels = $relatedModel::query()
            ->whereIn($foreignKey, $localKeys)
            ->get()
            ->getItemsGroupedBy($foreignKey);

        foreach ($models as $model) {
            $key = $model->$localKey;
            if (isset($relatedModels[$key])) {
                $model->setRelation($relation, new Collection($relatedModel, $relatedModels[$key]));
            }
        }
    }

    /**
     * Add a relationship to be eager loaded
     *
     * @param string $relation
     * @return self
     */
    public function embed($relation, $callback = null)
    {
        if (is_string($relation)) {
            $relation = [$relation => $callback];
        }

        foreach ($relation as $name => $constraint) {
            $this->eagerLoad[$name] = $constraint;
        }

        return $this;
    }

    /**
     * Execute the query and return an array of arrays.
     *
     * @return array
     * @throws PDOException
     */
    /**
     * Execute the query and return an array of arrays (for pagination).
     *
     * @return array
     * @throws PDOException
     */
    public function getForPagination(): array
    {
        try {
            $stmt = $this->pdo->prepare($this->toSql());
            $this->bindValues($stmt);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $models = array_map(fn($item) => new $this->modelClass($item), $results);

            $collection = new Collection($this->modelClass, $models);

            // Eager load relationships if any (same as in get())
            if (!empty($this->eagerLoad)) {
                $this->eagerLoadRelations($collection);
            }

            return $collection->all();
        } catch (PDOException $e) {
            throw new PDOException("Database error: " . $e->getMessage());
        }
    }

    /**
     * Execute the query and return the first result.
     *
     * @return mixed
     * @throws PDOException
     */
    public function first()
    {
        $this->limit(1);
        try {
            $stmt = $this->pdo->prepare($this->toSql());
            $this->bindValues($stmt);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? new $this->modelClass($result) : null;
        } catch (PDOException $e) {
            throw new PDOException("Database error: " . $e->getMessage());
        }
    }

    /**
     * Get the count of rows matching the current query.
     *
     * @return int
     * @throws PDOException
     */
    public function count(): int
    {
        try {
            if (!empty($this->groupBy)) {
                $sql = 'SELECT COUNT(DISTINCT ' . implode(', ', $this->groupBy) . ') as count FROM ' . $this->table;
            } else {
                $sql = 'SELECT COUNT(*) as count FROM ' . $this->table;
            }

            if (!empty($this->conditions)) {
                $conditionStrings = [];
                foreach ($this->conditions as $condition) {
                    $conditionStrings[] = "{$condition[1]} {$condition[2]} ?";
                }
                $sql .= ' WHERE ' . implode(' ', $this->formatConditions($conditionStrings));
            }

            $stmt = $this->pdo->prepare($sql);
            $this->bindValues($stmt);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['count'];
        } catch (PDOException $e) {
            throw new PDOException("Database error: " . $e->getMessage());
        }
    }

    /**
     * Get the records as per desc order
     *
     * @param string $column
     * @return self
     */
    public function newest(string $column = 'id'): self
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Get the records as per asc order
     *
     * @param string $column
     * @return self
     */
    public function oldest(string $column = 'id'): self
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * Check if any records exist for the current query.
     *
     * @return bool
     * @throws PDOException
     */
    public function exists(): bool
    {
        $this->limit(1);
        try {
            $stmt = $this->pdo->prepare($this->toSql());
            $this->bindValues($stmt);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            throw new PDOException("Database error: " . $e->getMessage());
        }
    }

    /**
     * Paginate the query results.
     *
     * @param int $perPage Number of items per page.
     * @param int $page Current page number.
     * @return array
     */
    public function paginate(?int $perPage = null): array
    {
        $page = request()->page ?? 1;
        $perPage = $perPage ?? $this->rowPerPage;

        if (!is_int($perPage) || $perPage <= 0) {
            $perPage = 15;
        }

        $offset = ($page - 1) * $perPage;
        $total = $this->count();
        $results = $this->limit($perPage)->offset($offset)->getForPagination();

        $lastPage = max(ceil($total / $perPage), 1);
        $path = URL::current();
        $from = $offset + 1;
        $to = min($offset + $perPage, $total);

        $firstPageUrl = "{$path}?page=1";
        $lastPageUrl = "{$path}?page={$lastPage}";
        $nextPageUrl = $page < $lastPage ? "{$path}?page=" . ($page + 1) : null;
        $prevPageUrl = $page > 1 ? "{$path}?page=" . ($page - 1) : null;

        return [
            'data' => $results,
            'first_page_url' => $firstPageUrl,
            'last_page_url' => $lastPageUrl,
            'next_page_url' => $nextPageUrl,
            'previous_page_url' => $prevPageUrl,
            'path' => $path,
            'from' => $from,
            'to' => $to,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
        ];
    }

    /**
     * Bind values to the prepared statement.
     *
     * @param PDOStatement $stmt
     * @return void
     */
    protected function bindValues(PDOStatement $stmt): void
    {
        $index = 1;
        foreach ($this->conditions as $condition) {
            if ($condition[2] === 'IN') {
                // For IN conditions, bind all values in the array
                foreach ($condition[3] as $value) {
                    $stmt->bindValue($index++, $value, $this->getPdoParamType($value));
                }
            } else {
                $stmt->bindValue($index++, $condition[3], $this->getPdoParamType($condition[3]));
            }
        }
    }

    /**
     * Insert a new record into the database.
     *
     * @param array $attributes
     * @return int|false The ID of the inserted record or false on failure.
     */
    public function insert(array $attributes)
    {
        $columns = implode(', ', array_keys($attributes));
        $values = implode(', ', array_fill(0, count($attributes), '?'));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($values)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $this->bindValuesForInsertOrUpdate($stmt, $attributes);
            $stmt->execute();
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            throw new PDOException("Database error: " . $e->getMessage());
        }
    }

    /**
     * Update records in the database.
     *
     * @param array $attributes
     * @return bool
     */
    public function update(array $attributes): bool
    {
        $setClause = implode(', ', array_map(fn($key) => "$key = ?", array_keys($attributes)));

        $sql = "UPDATE {$this->table} SET $setClause";

        if (!empty($this->conditions)) {
            $conditionStrings = [];
            foreach ($this->conditions as $condition) {
                $conditionStrings[] = "{$condition[1]} {$condition[2]} ?";
            }
            $sql .= ' WHERE ' . implode(' ', $this->formatConditions($conditionStrings));
        }

        try {
            $stmt = $this->pdo->prepare($sql);

            // Bind SET clause values
            $index = 1;
            foreach ($attributes as $value) {
                $stmt->bindValue($index++, $value, $this->getPdoParamType($value));
            }

            // Bind WHERE clause values
            foreach ($this->conditions as $condition) {
                $stmt->bindValue($index++, $condition[3], $this->getPdoParamType($condition[3]));
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            throw new PDOException("Database error: " . $e->getMessage());
        }
    }

    /**
     * Delete records from the database.
     *
     * @return bool Returns true if the delete operation was successful, false otherwise.
     * @throws PDOException If a database error occurs.
     */
    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->conditions)) {
            $conditionStrings = [];
            foreach ($this->conditions as $condition) {
                $conditionStrings[] = "{$condition[1]} {$condition[2]} ?";
            }
            $sql .= ' WHERE ' . implode(' ', $this->formatConditions($conditionStrings));
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $index = 1;
            foreach ($this->conditions as $condition) {
                $stmt->bindValue($index++, $condition[3], $this->getPdoParamType($condition[3]));
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            throw new PDOException("Database error: " . $e->getMessage());
        }
    }

    /**
     * Bind values for INSERT or UPDATE operations.
     *
     * @param PDOStatement $stmt
     * @param array $attributes
     * @return void
     */
    protected function bindValuesForInsertOrUpdate(PDOStatement $stmt, array $attributes): void
    {
        $index = 1;
        foreach ($attributes as $value) {
            $stmt->bindValue($index++, $value, $this->getPdoParamType($value));
        }
    }

    /**
     * Get the PDO param type for a value.
     *
     * @param mixed $value
     * @return int
     */
    protected function getPdoParamType($value): int
    {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        }
        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }
        if (is_null($value)) {
            return PDO::PARAM_NULL;
        }
        return PDO::PARAM_STR;
    }
}
