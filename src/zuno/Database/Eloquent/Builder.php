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
                $conditionStrings[] = "{$condition[1]} {$condition[2]} ?";
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

            return new Collection($this->modelClass, $models);
        } catch (PDOException $e) {
            throw new PDOException("Database error: " . $e->getMessage());
        }
    }

    /**
     * Execute the query and return an array of arrays.
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

            return array_map(fn($item) => new $this->modelClass($item), $results);
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
    public function paginate(int $page = 1): array
    {
        $page = request()->page ?? 1;
        $perPage = $this->rowPerPage;

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
        foreach ($this->conditions as $index => $condition) {
            $stmt->bindValue($index + 1, $condition[3], $this->getPdoParamType($condition[3]));
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
