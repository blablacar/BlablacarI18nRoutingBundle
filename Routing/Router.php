<?php

namespace Blablacar\I18nRoutingBundle\Routing;

use Blablacar\I18nRoutingBundle\Routing\Cache\CacheInterface;
use Blablacar\I18nRoutingBundle\Routing\Loader\I18nLoader;
use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\Request;

class Router extends BaseRouter
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Container
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Locale resolver
     *
     * @var LocaleResolverInterface
     */
    protected $localeResolver;

    /**
     * Generator
     *
     * @var UrlGeneratorInterface
     */
    protected $generator;

    /**
     * Loader
     *
     * @var I18nLoader
     */
    protected $loader;

    /**
     * Host map
     *
     * @var array
     */
    private $hostMap = array();

    /**
     * {@inheritDoc}
     */
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
     * @param string  $name       The name of the route
     * @param array   $parameters An array of parameters
     * @param Boolean $absolute   Whether to generate an absolute URL
     *
     * @return string The generated URL
     */
    public function generate($name, $parameters = array(), $absolute = false)
    {
        if (null === $name || empty($name)) {
            throw new \InvalidArgumentException('The route name is empty');
        }

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
     * matchRequest
     *
     * @param Request $request
     *
     * @return array|false An array of parameters or false if no route matches
     */
    public function matchRequest(Request $request)
    {
        return $this->match($request->getPathInfo());
    }

    /**
     * Tries to match a URL with a set of routes.
     *
     * Returns false if no route matches the URL.
     *
     * @param string $url URL to be parsed
     *
     * @return array|false An array of parameters or false if no route matches
     */
    public function match($url)
    {
        $params = $this->getMatcher()->match($url);

        if (false === $params) {
            throw new ResourceNotFoundException();
        }

        // No request. What append ?
        $currentLocale = null;
        if ($this->container->isScopeActive('request')) {
            $currentLocale = $this->localeResolver->resolveLocale(
                $this->container->get('request')
            );
        }

        // Clean the route name
        if (false !== $pos = strpos($params['_route'], I18nLoader::ROUTING_PREFIX)) {
            $params['_route'] = substr($params['_route'], $pos + strlen(I18nLoader::ROUTING_PREFIX));
        }

        // Retrieve all authorized locales for the given route
        $routeLocales = array();
        if (isset($params['_locale'])) {
            $routeLocales = array($params['_locale']);
        } elseif (isset($params['_locales'])) {
            $routeLocales = $params['_locales'];
            unset($params['_locales']);
        }

        if (0 === count($routeLocales) || in_array($currentLocale, $routeLocales)) {
            $params['_locale'] = $currentLocale;

            return $params;
        }

        throw new ResourceNotFoundException();
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

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        $collection = parent::getRouteCollection();

        return $this->loader->load($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function getOriginalRouteCollection()
    {
        return parent::getRouteCollection();
    }

    public function getCachedRouteCollection($name)
    {
        return $this->cache->getRoutes($name);
    }

    /**
     * @param UrlGeneratorInterface $generator
     */
    public function setGenerator(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @return UrlGeneratorInterface
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * @param LocaleResolverInterface $resolver
     */
    public function setLocaleResolver(LocaleResolverInterface $resolver)
    {
        $this->localeResolver = $resolver;
    }

    /**
     * @param I18nLoader $loader
     */
    public function setLoader(I18nLoader $loader)
    {
        $this->loader = $loader;
    }

    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }
}
