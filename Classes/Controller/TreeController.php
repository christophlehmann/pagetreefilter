<?php

namespace Lemming\PageTreeFilter\Controller;

use Lemming\PageTreeFilter\Domain\Repository\PageTreeRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

class TreeController extends \TYPO3\CMS\Backend\Controller\Page\TreeController
{
    public function filterDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $jsonResponse = parent::filterDataAction($request);
        $elements = json_decode($jsonResponse->getBody(), true);

        if (PageTreeRepository::$filterErrorneous === true) {
            $rootElement = array_pop($elements);
            $rootElement['name'] = sprintf('❌ %s', $this->getLanguageService()->sL('LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:filter_error'));
            $elements = [$rootElement];
        } else {
            if (PageTreeRepository::$filteredPageUids !== []) {
                foreach($elements as $key => $element) {
                    if (in_array($element['identifier'], PageTreeRepository::$filteredPageUids)) {
                        $elements[$key]['class'] = 'pagetreefilter-highlighted';
                    }
                }
            }
        }

        $jsonResponse = new JsonResponse($elements);

        return $jsonResponse;
    }
}