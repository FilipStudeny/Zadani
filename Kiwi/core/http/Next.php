<?php

namespace core\http;
class Next
{
    private array $modifiedData;

    public function __construct(?array $data = null)
    {
        $this->modifiedData = $data;
    }

    public function getModifiedData(): array
    {
        return $this->modifiedData;
    }

    public function setModifiedData($data): void
    {
        $this->modifiedData = array_merge($this->modifiedData, $data);
    }
}