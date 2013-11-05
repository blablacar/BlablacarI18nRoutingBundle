<?php

namespace Blablacar\I18nRoutingBundle\CacheWarmer;

use Blablacar\I18nRoutingBundle\Routing\Cache\CacheInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Routing\RouterInterface;

class RouterCacheWarmer implements CacheWarmerInterface
{
    /**
     * Router
     *
     * @var RouterInterface
     */
    protected $router;

    /**
     * Cache
     *
     * @var CacheInterface
     */
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
