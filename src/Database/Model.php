<?php

namespace SdFramework\Database;

abstract class Model
{
    protected static string $table;
    protected array $attributes = [];
    protected array $original = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): self
    {
        $this->attributes = $attributes;
        $this->original = $attributes;
        return $this;
    }

    public function __get(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    public static function find($id): ?static
    {
        $table = static::getTable();
        $stmt = Connection::getInstance()->query(
            "SELECT * FROM {$table} WHERE id = ?",
            [$id]
        );
        
        if ($row = $stmt->fetch()) {
            return new static($row);
        }
        
        return null;
    }

    public static function all(): array
    {
        $table = static::getTable();
        $stmt = Connection::getInstance()->query("SELECT * FROM {$table}");
        $models = [];
        
        while ($row = $stmt->fetch()) {
            $models[] = new static($row);
        }
        
        return $models;
    }

    public function save(): bool
    {
        $table = static::getTable();
        
        if (isset($this->attributes['id'])) {
            $sets = [];
            $params = [];
            
            foreach ($this->attributes as $column => $value) {
                if ($column === 'id') continue;
                $sets[] = "{$column} = ?";
                $params[] = $value;
            }
            
            $params[] = $this->attributes['id'];
            $setString = implode(', ', $sets);
            
            return Connection::getInstance()->query(
                "UPDATE {$table} SET {$setString} WHERE id = ?",
                $params
            )->rowCount() > 0;
        }
        
        $columns = array_keys($this->attributes);
        $values = array_fill(0, count($columns), '?');
        $columnString = implode(', ', $columns);
        $valueString = implode(', ', $values);
        
        $success = Connection::getInstance()->query(
            "INSERT INTO {$table} ({$columnString}) VALUES ({$valueString})",
            array_values($this->attributes)
        )->rowCount() > 0;
        
        if ($success) {
            $this->attributes['id'] = Connection::getInstance()->getPdo()->lastInsertId();
        }
        
        return $success;
    }

    public function delete(): bool
    {
        if (!isset($this->attributes['id'])) {
            return false;
        }

        $table = static::getTable();
        return Connection::getInstance()->query(
            "DELETE FROM {$table} WHERE id = ?",
            [$this->attributes['id']]
        )->rowCount() > 0;
    }

    protected static function getTable(): string
    {
        if (isset(static::$table)) {
            return static::$table;
        }

        $className = (new \ReflectionClass(static::class))->getShortName();
        return strtolower($className) . 's';
    }
}
