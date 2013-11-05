<?php

namespace Blablacar\I18nRoutingBundle\Routing\Loader;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Translation\TranslatorInterface;

class DefaultPatternGenerationStrategy implements PatternGenerationStrategyInterface
{
    /**
     * Translator
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Translation domain
     *
     * @var string
     */
    private $translationDomain;

    /**
     * Locales
     *
     * @var array
     */
    private $locales;

    /**
     * Cache directory
     *
     * @var string
     */
    private $cacheDir;
    private $defaultLocale;

    /**
     * @param TranslatorInterface $translator
     * @param array               $locales
     * @param string              $cacheDir
     * @param string              $translationDomain
     * @param string              $defaultLocale
     */
    public function __construct(TranslatorInterface $translator, array $locales, $cacheDir, $translationDomain = 'routes', $defaultLocale = 'en_GB')
    {
        $this->translator        = $translator;
        $this->locales           = $locales;
        $this->cacheDir          = $cacheDir;
        $this->translationDomain = $translationDomain;
        $this->defaultLocale     = $defaultLocale;
    }

    /**
     * {@inheritDoc}
     */
    public function generateI18nPatterns($routeName, Route $route)
    {
        $locales  = $route->getOption('i18n_locales') ?: $this->locales;
        $patterns = array();

        // "routes" option which store all translations for a given route (TODO: required after refacto)
        if (null !== ($routes = $route->getOption('routes')) && !isset($routes[$this->defaultLocale])) {
            throw new \InvalidArgumentException(sprintf('The "path" option for the route "%s" must have at least the %s translation.', $routeName, $this->defaultLocale));
        }

        foreach ($locales as $locale) {
            // if no translation exists, we use the current pattern
            $i18nPattern = $this->translator->trans($routeName, array(), $this->translationDomain, $locale);

            // overload the routes' translations from translations' files by the routes' translations from route' files
            if (null !== $routes) {
                $i18nPattern = (isset($routes[$locale]) ? $routes[$locale] : $routes[$this->defaultLocale]);
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
            $metadata = $this->getMetaDataFilePath($locale);

            if (!file_exists($metadata)) {
                continue;
            }

            foreach (unserialize(file_get_contents($metadata)) as $resource) {
                $i18nCollection->addResource($resource);
            }
        }
    }

    /**
     * Gets the metadata file path according given locale
     *
     * @param string $locale
     *
     * @return string
     */
    private function getMetaDataFilePath($locale)
    {
        $metadata = sprintf(
            '%s/translations/catalogue/catalogue.%s.php.meta',
            $this->cacheDir,
            $locale
        );

        return $metadata;
    }
}
