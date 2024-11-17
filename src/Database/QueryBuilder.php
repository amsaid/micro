<?php

namespace SdFramework\Database;

class QueryBuilder
{
    private string $table;
    private array $wheres = [];
    private array $bindings = [];
    private array $orders = [];
    private array $groups = [];
    private array $joins = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $columns = ['*'];

    public function __construct(private Connection $connection)
    {
    }

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(array|string $columns = ['*']): self
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function where(string $column, string $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        $this->bindings[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'placeholders' => $placeholders
        ];

        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'inner',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'left',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];

        return $this;
    }

    public function groupBy(string|array $columns): self
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->groups = array_merge($this->groups, $columns);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->toSql();
        return $this->connection->query($sql, $this->bindings)->fetchAll();
    }

    public function first()
    {
        $result = $this->limit(1)->get();
        return $result[0] ?? null;
    }

    public function count(): int
    {
        $this->columns = ['COUNT(*) as count'];
        $result = $this->get();
        return (int) ($result[0]['count'] ?? 0);
    }

    public function insert(array $values): bool
    {
        $columns = array_keys($values);
        $placeholders = str_repeat('?,', count($values) - 1) . '?';

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            $placeholders
        );

        return $this->connection->query($sql, array_values($values))->rowCount() > 0;
    }

    public function update(array $values): bool
    {
        $sets = [];
        $bindings = [];

        foreach ($values as $column => $value) {
            $sets[] = "{$column} = ?";
            $bindings[] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s %s',
            $this->table,
            implode(', ', $sets),
            $this->compileWheres()
        );

        $bindings = array_merge($bindings, $this->bindings);
        return $this->connection->query($sql, $bindings)->rowCount() > 0;
    }

    public function delete(): bool
    {
        $sql = sprintf('DELETE FROM %s %s', $this->table, $this->compileWheres());
        return $this->connection->query($sql, $this->bindings)->rowCount() > 0;
    }

    private function toSql(): string
    {
        $sql = ['SELECT', implode(', ', $this->columns)];
        $sql[] = 'FROM';
        $sql[] = $this->table;

        if (!empty($this->joins)) {
            $sql[] = $this->compileJoins();
        }

        if (!empty($this->wheres)) {
            $sql[] = $this->compileWheres();
        }

        if (!empty($this->groups)) {
            $sql[] = 'GROUP BY';
            $sql[] = implode(', ', $this->groups);
        }

        if (!empty($this->orders)) {
            $sql[] = 'ORDER BY';
            $sql[] = $this->compileOrders();
        }

        if ($this->limit !== null) {
            $sql[] = 'LIMIT';
            $sql[] = $this->limit;
        }

        if ($this->offset !== null) {
            $sql[] = 'OFFSET';
            $sql[] = $this->offset;
        }

        return implode(' ', $sql);
    }

    private function compileWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $conditions = [];

        foreach ($this->wheres as $where) {
            if ($where['type'] === 'basic') {
                $conditions[] = sprintf(
                    '%s %s ?',
                    $where['column'],
                    $where['operator']
                );
            } elseif ($where['type'] === 'in') {
                $conditions[] = sprintf(
                    '%s IN (%s)',
                    $where['column'],
                    $where['placeholders']
                );
            }
        }

        return 'WHERE ' . implode(' AND ', $conditions);
    }

    private function compileJoins(): string
    {
        $sql = [];

        foreach ($this->joins as $join) {
            $sql[] = sprintf(
                '%s JOIN %s ON %s %s %s',
                strtoupper($join['type']),
                $join['table'],
                $join['first'],
                $join['operator'],
                $join['second']
            );
        }

        return implode(' ', $sql);
    }

    private function compileOrders(): string
    {
        return implode(', ', array_map(function($order) {
            return $order['column'] . ' ' . $order['direction'];
        }, $this->orders));
    }
}
