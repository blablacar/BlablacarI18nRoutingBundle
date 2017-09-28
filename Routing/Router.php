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
use Symfony\Component\Routing\RouteCollection;

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
    private $hostMap = [];

    /**
     * {@inheritDoc}
     */
    public function __construct(
        ContainerInterface $container,
        $resource,
        array $options = [],
        RequestContext $context = null
    ) {
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
     * @param string $name          The name of the route
     * @param array  $parameters    An array of parameters
     * @param int    $referenceType Generate an absolute URL or PATH.
     *
     * @return string The generated URL
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        if (null === $name || empty($name)) {
            throw new \InvalidArgumentException('The route name is empty');
        }

        // determine the most suitable locale to use for route generation
        $currentLocale = $this->context->getParameter('_locale');
        if (isset($parameters['_locale']) && $parameters['_locale'] !== null) {
            $locale = $parameters['_locale'];
        } else {
            $locale = $currentLocale;
        }

        // if the locale is changed, and we have a host map, then we need to
        // generate an absolute URL
        if ($currentLocale && $currentLocale !== $locale && $this->hostMap) {
            $referenceType = self::ABSOLUTE_URL;
        }

        $generator = $this->getGenerator();
        $currentHost = '';

        // if an absolute URL is requested, we set the correct host
        if (!$referenceType && $this->hostMap) {
            $currentHost = $this->context->getHost();
            $this->context->setHost($this->hostMap[$locale]);
        }

        try {
            $cachedRoutesLocale = 'en_GB';

            $cachedRoutes = $this->getCachedRouteCollection($cachedRoutesLocale);

            $redirectToLocale = null;

            if (isset($cachedRoutes[$cachedRoutesLocale . I18nLoader::ROUTING_PREFIX . $name])) {
                $redirectToLocale = $cachedRoutes[$cachedRoutesLocale . I18nLoader::ROUTING_PREFIX . $name]->getOption('redirect_to_locale');
            }

            if ($redirectToLocale !== null && $redirectToLocale !== $locale) {

                $locale = $redirectToLocale;

                unset($parameters['_locale']);
            }

            $url = $generator->generate($locale . I18nLoader::ROUTING_PREFIX . $name, $parameters, $referenceType);

            if (!$referenceType && $this->hostMap) {
                $this->context->setHost($currentHost);
            }

            return $url;
        } catch (RouteNotFoundException $ex) {
            if (!$referenceType && $this->hostMap) {
                $this->context->setHost($currentHost);
            }

            // fallback to default behavior
        }

        // use the default behavior if no localized route exists
        return $generator->generate($name, $parameters, $referenceType);
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

        $cachedRoutesLocale = $currentLocale = 'en_GB';
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request instanceof Request) {
            $currentLocale = $this->localeResolver->resolveLocale($request);
        }

        // Clean the route name
        if (false !== $pos = strpos($params['_route'], I18nLoader::ROUTING_PREFIX)) {
            $params['_route'] = substr($params['_route'], $pos + strlen(I18nLoader::ROUTING_PREFIX));
        }

        // Retrieve all authorized locales for the given route
        $routeLocales = [];
        if (isset($params['_locale'])) {
            $routeLocales = [$params['_locale']];
        } elseif (isset($params['_locales'])) {
            $routeLocales = $params['_locales'];
            unset($params['_locales']);
        }

        $routes = $this->getCachedRouteCollection($cachedRoutesLocale);

        $redirectToLocale = null;

        if (isset($routes[$cachedRoutesLocale . I18nLoader::ROUTING_PREFIX . $params['_route']])) {
            $redirectToLocale = $routes[$cachedRoutesLocale . I18nLoader::ROUTING_PREFIX . $params['_route']]->getOption('redirect_to_locale');
        }

        if ($redirectToLocale !== null && $redirectToLocale !== $currentLocale) {
            $routeLocales[] = $currentLocale;
        }

        if (0 === count($routeLocales) || in_array($currentLocale, $routeLocales)) {
            // don't add _locale parameter to redirects
            if (isset($params['_controller']) && $params['_controller'] != 'FrameworkBundle:Redirect:redirect') {
                $params['_locale'] = $currentLocale;
            }

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
        $cache = new ConfigCache($this->options['cache_dir'] . '/' . $class . '.php', $this->options['debug']);
        if (!$cache->isFresh()) {
            $routeCollection = $this->getCleanRouteCollection();
            $dumper = new $this->options['matcher_dumper_class']($routeCollection);

            $options = [
                'class'      => $class,
                'base_class' => $this->options['matcher_base_class'],
            ];

            $cache->write($dumper->dump($options), $routeCollection->getResources());
        }

        require_once $cache->getPath();

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

    /**
     * @param string $name
     *
     * @return array
     */
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

    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }
}
