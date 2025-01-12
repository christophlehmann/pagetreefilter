<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Utility;

use Lemming\PageTreeFilter\Domain\Dto\Result;
use Lemming\PageTreeFilter\Middleware\PageTreeFilterMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class RequestUtility
{
    public static function getResult(): ?Result
    {
        return self::getServerRequest()->getAttribute(PageTreeFilterMiddleware::ATTRIBUTE);
    }

    protected static function getServerRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
