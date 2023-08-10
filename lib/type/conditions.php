<?php
namespace Reaspekt\Geobase\Type;

class Conditions
{
    private array $filter;
    private int $limit = 0;
    private int $offset = 0;

    public function getFilter(): array
    {
        return $this->filter;
    }

    public function setFilter(array $arFilter): Conditions
    {
        $this->filter = $arFilter;
        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): Conditions
    {
        $this->limit = $limit;
        return $this;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): Conditions
    {
        $this->offset = $offset;
        return $this;
    }
}