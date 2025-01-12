<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Domain\Dto;

class Result
{
    public function __construct(
        protected readonly array $recordUids,
        protected readonly Filter $filter,
        protected readonly bool $isValidFilter
    )
    {}

    public function getRecordUids(): array
    {
        return $this->recordUids;
    }

    public function getFilter(): Filter
    {
        return $this->filter;
    }

    public function isValidFilter(): bool
    {
        return $this->isValidFilter;
    }
}