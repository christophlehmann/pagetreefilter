<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Domain\Dto;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Filter
{
    protected ?string $table = null;
    protected array $constraints = [];
    protected ?string $remainingSearchQuery = null;

    public function __construct(
        protected string $rawQuery,
        protected bool $isHighlighting = false,
        protected ?int $currentPage = null
    ) {
        $this->buildContraints($rawQuery);
    }

    protected function buildContraints(string $searchFilter)
    {
        $remainingSearchFilterParts = [];
        foreach (GeneralUtility::trimExplode(' ', $searchFilter, true) as $queryPart) {
            $filter = GeneralUtility::trimExplode('=', $queryPart);
            if (count($filter) == 2) {
                switch ($filter[0]) {
                    case 'table':
                        $this->table = $filter[1];
                        break;
                    default:
                        $this->constraints[] = [
                            'field' => $filter[0],
                            'value' => $filter[1]
                        ];
                }
            } else {
                $remainingSearchFilterParts[] = $queryPart;
            }
        }

        $this->remainingSearchQuery = implode(' ', $remainingSearchFilterParts);
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function getRemainingSearchQuery(): ?string
    {
        return $this->remainingSearchQuery;
    }

    public function getRawQuery(): string
    {
        return $this->rawQuery;
    }

    public function isHighlighting(): bool
    {
        return $this->isHighlighting;
    }

    public function getCurrentPage(): ?int
    {
        return $this->currentPage;
    }
}