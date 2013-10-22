<?php

namespace Blablacar\I18nRoutingBundle\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RequestContext;
use Blablacar\I18nRoutingBundle\Routing\Loader\I18nLoader;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class Router extends BaseRouter
{
    private $container;

    protected $localeResolver;
    protected $generator;
    protected $loader;

    private $hostMap = array();

    public function __construct(ContainerInterface $container, $resource, array $options = array(), RequestContext $context = null)
    {
        $this->container = $container;
        parent::__construct($container, $resource, $options, $context);
    }





    /**
     * Sets the host map to use.
     *
     * @param array $hostMap a map of locales to hosts
     */
    public function setHostMap(array $hostMap)
    {
        $this->hostMap = $hostMap;
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param  string  $name       The name of the route
     * @param  array   $parameters An array of parameters
     * @param  Boolean $absolute   Whether to generate an absolute URL
     *
     * @return string The generated URL
     */
    public function generate($name, $parameters = array(), $absolute = false)
    {
        // determine the most suitable locale to use for route generation
        $currentLocale = $this->context->getParameter('_locale');
        if (isset($parameters['_locale'])) {
            $locale = $parameters['_locale'];
        } else {
            $locale = $currentLocale;
        }

        // if the locale is changed, and we have a host map, then we need to
        // generate an absolute URL
        if ($currentLocale && $currentLocale !== $locale && $this->hostMap) {
            $absolute = true;
        }

        $generator = $this->getGenerator();

        // if an absolute URL is requested, we set the correct host
        if ($absolute && $this->hostMap) {
            $currentHost = $this->context->getHost();
            $this->context->setHost($this->hostMap[$locale]);
        }

        try {
            $url = $generator->generate($locale.I18nLoader::ROUTING_PREFIX.$name, $parameters, $absolute);

            if ($absolute && $this->hostMap) {
                $this->context->setHost($currentHost);
            }

            return $url;
        } catch (RouteNotFoundException $ex) {
            if ($absolute && $this->hostMap) {
                $this->context->setHost($currentHost);
            }

            // fallback to default behavior
        }


        // use the default behavior if no localized route exists
        return $generator->generate($name, $parameters, $absolute);
    }

    /**
     * Tries to match a URL with a set of routes.
     *
     * Returns false if no route matches the URL.
     *
     * @param  string $url URL to be parsed
     *
     * @return array|false An array of parameters or false if no route matches
     */
    public function match($url)
    {
        $params = $this->getMatcher()->match($url);

        if (false === $params) {
            return false;
        }

        // No request. What append ?
        if ($this->container->isScopeActive('request')) {
            $currentLocale = $this->localeResolver->resolveLocale(
                $this->container->get('request')
            );
        }

        // Only 1 locale available for the retrieved route
        if (isset($params['_locale'])) {
            if ($currentLocale === $params['locale']) {
                return $params;
            } else {
                return false;
            }
        }

        if (!isset($params['_locales'])) {
            return false;
        }

        // The current locale is not found for the retrieved route
        if (!in_array($currentLocale, $params['_locales'], true)) {
            return false;
        }

        if (false !== $pos = strpos($params['_route'], I18nLoader::ROUTING_PREFIX)) {
            $params['_route'] = substr($params['_route'], $pos + strlen(I18nLoader::ROUTING_PREFIX));
        }

        unset($params['_locales']);
        $params['_locale'] = $currentLocale;

        return $params;
    }

    /**
     * {@inheritDoc}
     *
     * Override default method to filter the RouteCollection BEFORE building the UrlMatcher
     */
    public function getMatcher()
    {
        if (null !== $this->matcher) {
            return $this->matcher;
        }

        if (null === $this->options['cache_dir'] || null === $this->options['matcher_cache_class']) {
            return $this->matcher = new $this->options['matcher_class'](
                $this->getCleanRouteCollection(),
                $this->context
            );
        }

        $class = $this->options['matcher_cache_class'];
        $cache = new ConfigCache($this->options['cache_dir'].'/'.$class.'.php', $this->options['debug']);
        if (!$cache->isFresh($class)) {
            $routeCollection = $this->getCleanRouteCollection();
            $dumper = new $this->options['matcher_dumper_class']($routeCollection);

            $options = array(
                'class'      => $class,
                'base_class' => $this->options['matcher_base_class'],
            );

            $cache->write($dumper->dump($options), $routeCollection->getResources());
        }

        require_once $cache;

        return $this->matcher = new $class($this->context);
    }

    /**
     * getCleanRouteCollection
     *
     * @return RouteCollection
     */
    protected function getCleanRouteCollection()
    {
        $cleaner = new RouteCollectionCleaner();

        return $cleaner->clean($this->getRouteCollection());
    }

    public function getRouteCollection()
    {
        $collection = parent::getRouteCollection();

        return $this->loader->load($collection);
    }

    public function getOriginalRouteCollection()
    {
        return parent::getRouteCollection();
    }


    public function setGenerator(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    public function getGenerator()
    {
        return $this->generator;
    }

    public function setLocaleResolver(LocaleResolverInterface $resolver)
    {
        $this->localeResolver = $resolver;
    }

    public function setLoader(I18nLoader $loader)
    {
        $this->loader = $loader;
    }
}
