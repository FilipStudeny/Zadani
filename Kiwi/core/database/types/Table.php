<?php

namespace core\database\types;

class Table {
    private string $name;
    private array $columns = [];

    public function __construct(string $name, array $columnDefinitions = []) {
        $this->name = $name;
        foreach ($columnDefinitions as $columnName => $columnType) {
            $this->addColumn($columnName, $columnType);
        }
    }

    public function getName(): string {
        return $this->name;
    }

    public function addColumn(string $columnName, array $columnDefinition): self {
        $this->columns[$columnName] = $columnDefinition;
        return $this;
    }

    public function getColumns(): array {
        return $this->columns;
    }
}