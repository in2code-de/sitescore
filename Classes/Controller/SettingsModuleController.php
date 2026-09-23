<?php

declare(strict_types=1);

namespace In2code\Sitescore\Controller;

use In2code\Sitescore\Domain\Service\SettingsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

#[AsController]
class SettingsModuleController
{
    private const MODULE_IDENTIFIER = 'in2code_ai_sitescore';
    private const LANGUAGE_FILE = 'LLL:EXT:sitescore/Resources/Private/Language/Backend/locallang_module.xlf:';

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly SettingsService $settingsService,
        private readonly UriBuilder $uriBuilder,
        private readonly FlashMessageService $flashMessageService,
        private readonly Context $context,
    ) {
    }

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($this->translate('module.title'));
        $view->assignMultiple([
            'settings' => $this->settingsService->getSettingsForView($this->isAdmin()),
            'saveUri' => (string)$this->uriBuilder->buildUriFromRoute(self::MODULE_IDENTIFIER . '.save'),
        ]);
        return $view->renderResponse('SettingsModule/Index');
    }

    public function saveAction(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array)$request->getParsedBody();
        $invalidKeys = $this->settingsService->save(
            (array)($body['settings'] ?? []),
            (array)($body['removeSecrets'] ?? []),
            $this->isAdmin()
        );
        $this->addSaveMessage($invalidKeys);
        return new RedirectResponse($this->uriBuilder->buildUriFromRoute(self::MODULE_IDENTIFIER));
    }

    protected function addSaveMessage(array $invalidKeys): void
    {
        $message = new FlashMessage($this->translate('message.saved'), '', ContextualFeedbackSeverity::OK, true);
        if ($invalidKeys !== []) {
            $message = new FlashMessage(
                $this->translate('message.invalid') . ' ' . implode(', ', $invalidKeys),
                '',
                ContextualFeedbackSeverity::WARNING,
                true
            );
        }
        $this->flashMessageService->getMessageQueueByIdentifier()->addMessage($message);
    }

    protected function isAdmin(): bool
    {
        return (bool)$this->context->getPropertyFromAspect('backend.user', 'isAdmin', false);
    }

    protected function translate(string $key): string
    {
        return (string)LocalizationUtility::translate(self::LANGUAGE_FILE . $key);
    }
}
