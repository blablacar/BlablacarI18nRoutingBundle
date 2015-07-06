<?php

namespace Blablacar\I18nRoutingBundle\Routing\Loader;

use Symfony\Component\Routing\RouteCollection;

/**
 * This loader expands all routes which are eligible for i18n.
 */
class I18nLoader
{
    const ROUTING_PREFIX = '__RG__';

    /**
     * @var RouteExclusionStrategyInterface
     */
    private $routeExclusionStrategy;

    /**
     * @var PatternGenerationStrategyInterface
     */
    private $patternGenerationStrategy;

    /**
     * @param RouteExclusionStrategyInterface    $routeExclusionStrategy
     * @param PatternGenerationStrategyInterface $patternGenerationStrategy
     */
    public function __construct(RouteExclusionStrategyInterface $routeExclusionStrategy, PatternGenerationStrategyInterface $patternGenerationStrategy)
    {
        $this->routeExclusionStrategy = $routeExclusionStrategy;
        $this->patternGenerationStrategy = $patternGenerationStrategy;
    }

    /**
     * @param RouteCollection $collection
     *
     * @return RouteCollection
     */
    public function load(RouteCollection $collection)
    {
        $i18nCollection = new RouteCollection();
        foreach ($collection->getResources() as $resource) {
            $i18nCollection->addResource($resource);
        }
        $this->patternGenerationStrategy->addResources($i18nCollection);

        foreach ($collection->all() as $name => $route) {
            if ($this->routeExclusionStrategy->shouldExcludeRoute($name, $route)) {
                $i18nCollection->add($name, $route);
                continue;
            }

            foreach ($this->patternGenerationStrategy->generateI18nPatterns($name, $route) as $pattern => $locales) {
                // If this pattern is used for more than one locale, we need to keep the original route.
                // We still add individual routes for each locale afterwards for faster generation.
                if (count($locales) > 1) {
                    $catchMultipleRoute = clone $route;
                    $catchMultipleRoute->setPath($pattern);
                    $catchMultipleRoute->setDefault('_locales', $locales);
                    $i18nCollection->add(implode('_', $locales).I18nLoader::ROUTING_PREFIX.$name, $catchMultipleRoute);
                }

                foreach ($locales as $locale) {
                    $localeRoute = clone $route;
                    $localeRoute->setPath($pattern);
                    $localeRoute->setDefault('_locale', $locale);
                    $i18nCollection->add($locale.I18nLoader::ROUTING_PREFIX.$name, $localeRoute);
                }
            }
        }

        return $i18nCollection;
    }
}
