<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Controller;

use Lemming\PageTreeFilter\Domain\Repository\PageTreeRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Dto\Tree\Label\Label;
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
            if (PageTreeRepository::$filteredPageUids !== []) {
                $label = $this->getLanguageService()->sL('LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:result_item_label');
                foreach($elements as $key => $element) {
                    if (in_array($element['identifier'], PageTreeRepository::$filteredPageUids)) {
                        $elements[$key]['labels'][] = new Label(
                            label: $label,
                            color: '#6daae0',
                            priority: 100
                        );
                    }
                }
            }
        }

        $jsonResponse = new JsonResponse($elements);

        return $jsonResponse;
    }
}