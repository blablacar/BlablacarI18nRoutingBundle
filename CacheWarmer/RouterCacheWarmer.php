<?php

namespace Blablacar\I18nRoutingBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\RouteCollection;
use Blablacar\I18nRoutingBundle\Routing\Cache\CacheInterface;

class RouterCacheWarmer implements CacheWarmerInterface
{
    protected $router;
    protected $cache;

    public function __construct(RouterInterface $router, CacheInterface $cache)
    {
        $this->router = $router;
        $this->cache  = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp($cacheDir)
    {
        $routeCollection = $this->router->getRouteCollection();
        $this->cache->storeRouteCollection($routeCollection);
    }

    /**
     * {@inheritDoc}
     */
    public function isOptional()
    {
        return false;
    }
}
