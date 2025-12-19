<?php

declare(strict_types=1);

namespace In2code\Sitescore\EventListener;

#[AsEventListener(
    identifier: 'sitescore/pagemodule',
    event: \TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent::class,
)]
class FileControlsEventListener
{
    public function __construct(
    ) {
    }

    public function __invoke(\TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent $event): void
    {
    }
}
