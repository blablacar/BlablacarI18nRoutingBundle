<?php

namespace Blablacar\I18nRoutingBundle\Routing\Loader;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\Route;

class DefaultPatternGenerationStrategy implements PatternGenerationStrategyInterface
{
    private $translator;
    private $translationDomain;
    private $locales;
    private $cacheDir;

    public function __construct(TranslatorInterface $translator, array $locales, $cacheDir, $translationDomain = 'routes')
    {
        $this->translator        = $translator;
        $this->locales           = $locales;
        $this->cacheDir          = $cacheDir;
        $this->translationDomain = $translationDomain;
    }

    /**
     * {@inheritDoc}
     */
    public function generateI18nPatterns($routeName, Route $route)
    {
        $locales  = $route->getOption('i18n_locales') ?: $this->locales;
        $patterns = array();

        // "routes" option which store all translations for a given route (TODO: required after refacto)
        if (null !== ($routes = $route->getOption('routes')) && !isset($routes['en_GB'])) {
            throw new \InvalidArgumentException(sprintf('The "path" option for the route "%s" must have at least the en_GB translation.', $routeName));
        }

        foreach ($locales as $locale) {
            // if no translation exists, we use the current pattern
            $i18nPattern = $this->translator->trans($routeName, array(), $this->translationDomain, $locale);

            // overload the routes' translations from translations' files by the routes' translations from route' files
            if (null !== $routes) {
                $i18nPattern = (isset($routes[$locale]) ? $routes[$locale] : $routes['en_GB']);
            }

            if ($routeName === $i18nPattern) {
                $i18nPattern = $route->getPattern();
            }

            $patterns[$i18nPattern][] = $locale;
        }

        return $patterns;
    }

    /**
     * {@inheritDoc}
     */
    public function addResources(RouteCollection $i18nCollection)
    {
        foreach ($this->locales as $locale) {
            if (file_exists($metadata = $this->cacheDir.'/translations/catalogue.'.$locale.'.php.meta')) {
                foreach (unserialize(file_get_contents($metadata)) as $resource) {
                    $i18nCollection->addResource($resource);
                }
            }
        }
    }
}
