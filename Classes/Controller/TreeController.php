<?php
declare(strict_types=1);

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
        $elements = json_decode($jsonResponse->getBody()->getContents(), true);

        if (PageTreeRepository::$filterErrorneous === true) {
            $rootElement = array_pop($elements);
            $rootElement['name'] = sprintf('❌ %s', $this->getLanguageService()->sL('LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:filter_error'));
            $elements = [$rootElement];
        } else {
            foreach ($elements as $key => $element) {
                foreach (PageTreeRepository::$resultSets as $resultSet) {
                    if (in_array($element['identifier'], $resultSet['pageUids'])) {
                        if (isset($resultSet['description'])) {
                            $elements[$key]['tip'] .= sprintf(', %s', $resultSet['description']);
                        }
                        $elements[$key]['class'] = 'pagetreefilter-highlighted';
                        $elements[$key]['backgroundColor'] = $resultSet['backgroundColor'] ?? '#0078e6';
                    }
                }
            }
        }

        $jsonResponse = new JsonResponse($elements);

        return $jsonResponse;
    }
}