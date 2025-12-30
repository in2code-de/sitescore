<?php

declare(strict_types=1);

namespace In2code\Sitescore\EventListener;

use In2code\Sitescore\Events\TemplatePageModuleEvent;
use In2code\Sitescore\Utility\BackendUserUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

#[AsEventListener(
    identifier: 'sitescore/pagemodule',
    event: ModifyPageLayoutContentEvent::class,
)]
class PageModuleEventListener
{
    public function __construct(
        protected readonly PageRenderer $pageRenderer,
        protected readonly ViewFactoryInterface $viewFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(ModifyPageLayoutContentEvent $event): void
    {
        if ($this->isActivated($event)) {
            /** @var TemplatePageModuleEvent $eventTemplate */
            $eventTemplate = $this->eventDispatcher->dispatch(new TemplatePageModuleEvent());
            $viewFactoryData = new ViewFactoryData(
                templateRootPaths: $eventTemplate->getTemplates(),
                partialRootPaths: $eventTemplate->getPartials(),
                layoutRootPaths: $eventTemplate->getLayouts(),
                request: $event->getRequest(),
            );
            $view = $this->viewFactory->create($viewFactoryData);
            $view->assignMultiple([
                'pageId' => $this->getPageIdentifier($event),
                'languageId' => $this->getLanguageIdentifier($event),
                'event' => $event,
                'collapsed' => BackendUserUtility::isCollapsed(),
            ] + $eventTemplate->getAdditionialAssignments());

            $event->setHeaderContent($view->render('ScoreDashboard') . $event->getHeaderContent());
        }
    }

    protected function isActivated(ModifyPageLayoutContentEvent $event): bool
    {
        return $this->getPageIdentifier($event) > 0;
    }

    protected function getPageIdentifier(ModifyPageLayoutContentEvent $event): int
    {
        return (int)($event->getRequest()->getParsedBody()['id'] ?? $event->getRequest()->getQueryParams()['id'] ?? 0);
    }

    protected function getLanguageIdentifier(ModifyPageLayoutContentEvent $event): int
    {
        $language = (int)($event->getRequest()->getQueryParams()['languages'][0] ?? 0);
        // Todo: Can be dropped once TYPO3 13 support is dropped
        if ((new Typo3Version())->getMajorVersion() === 13) {
            $language = (int)($event->getRequest()->getQueryParams()['language'] ?? 0);
        }
        return $language;
    }
}
