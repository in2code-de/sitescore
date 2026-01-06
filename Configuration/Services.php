<?php

declare(strict_types=1);

use In2code\Sitescore\Widgets\Provider\ScoreDistributionDataProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Dashboard\Dashboard;
use TYPO3\CMS\Dashboard\Widgets\DoughnutChartWidget;

return function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder) {
    $llPrefix = 'LLL:EXT:sitescore/Resources/Private/Language/Backend/locallang.xlf:';
    $services = $configurator->services();

    // Dashboard widgets - only register if typo3/cms-dashboard is installed
    if ($containerBuilder->hasDefinition(Dashboard::class)) {
        // Data providers - one per category
        $categories = ['geo', 'performance', 'semantics', 'keywords', 'accessibility'];
        foreach ($categories as $category) {
            $services->set('dashboard.provider.sitescore.' . $category)
                ->class(ScoreDistributionDataProvider::class)
                ->autowire()
                ->arg('$category', $category);
        }

        // Widgets - one per category
        $widgetConfig = [
            'geo' => ['identifier' => 'sitescore_geo', 'title' => 'dashboard.widget.geo.title'],
            'performance' => ['identifier' => 'sitescore_performance', 'title' => 'dashboard.widget.performance.title'],
            'semantics' => ['identifier' => 'sitescore_semantics', 'title' => 'dashboard.widget.semantics.title'],
            'keywords' => ['identifier' => 'sitescore_keywords', 'title' => 'dashboard.widget.keywords.title'],
            'accessibility' => ['identifier' => 'sitescore_accessibility', 'title' => 'dashboard.widget.accessibility.title'],
        ];

        foreach ($widgetConfig as $category => $config) {
            $services->set('dashboard.widget.sitescore.' . $category)
                ->class(DoughnutChartWidget::class)
                ->arg('$dataProvider', new Reference('dashboard.provider.sitescore.' . $category))
                ->arg('$backendViewFactory', new Reference(BackendViewFactory::class))
                ->tag('dashboard.widget', [
                    'identifier' => $config['identifier'],
                    'groupNames' => 'sitescore',
                    'title' => $llPrefix . $config['title'],
                    'description' => $llPrefix . 'dashboard.widget.description',
                    'iconIdentifier' => 'content-dashboard',
                    'height' => 'medium',
                    'width' => 'small',
                ]);
        }
    }
};
