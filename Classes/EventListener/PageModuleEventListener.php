<?php

declare(strict_types=1);

namespace In2code\Sitescore\EventListener;

use In2code\Sitescore\Events\TemplatePageModuleEvent;
use In2code\Sitescore\Utility\BackendUserUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
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
            $this->pageRenderer->addCssFile('EXT:sitescore/Resources/Public/Css/Backend.css');
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::create('@in2code/sitescore/Backend.js')
            );

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
                'event' => $event,
                'collapsed' => BackendUserUtility::isCollapsed(),
            ] + $eventTemplate->getAdditionialAssignments());

            $event->setHeaderContent($view->render('ScoreDashboard') . $event->getHeaderContent());
        }
    }

    private function isActivated(ModifyPageLayoutContentEvent $event): bool
    {
        return $this->getPageIdentifier($event) > 0;
    }

    private function getPageIdentifier(ModifyPageLayoutContentEvent $event): int
    {
        return (int)($event->getRequest()->getParsedBody()['id'] ?? $event->getRequest()->getQueryParams()['id'] ?? 0);
    }
}
