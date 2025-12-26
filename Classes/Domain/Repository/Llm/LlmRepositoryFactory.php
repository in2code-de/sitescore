<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Repository\Llm;

use In2code\Sitescore\Exception\ConfigurationException;
use Psr\Container\ContainerInterface;

/**
 * Class LlmRepositoryFactory
 * to allow registering own Repositories to use other language models (e.g. ChatGPT, Claude, Copilot) with
 * $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['sitescore']['llmRepositoryClass'] = MyRepository::class;
 * (ensure that MyRepository implements RepositoryInterface class)
 */
class LlmRepositoryFactory
{
    protected string $defaultRepositoryClass = GeminiRepository::class;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public function create(): RepositoryInterface
    {
        // Allow third-party extensions to override the LLM repository implementation
        $repositoryClass = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['sitescore']['llmRepositoryClass']
            ?? $this->defaultRepositoryClass;

        if (is_a($repositoryClass, RepositoryInterface::class, true) === false) {
            throw new ConfigurationException(
                sprintf(
                    'LLM repository class "%s" must implement %s',
                    $repositoryClass,
                    RepositoryInterface::class
                ),
                1766746451
            );
        }

        return $this->container->get($repositoryClass);
    }
}
