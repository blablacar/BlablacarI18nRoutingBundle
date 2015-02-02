<?php

namespace Blablacar\I18nRoutingBundle\Routing\Cache;

use Symfony\Component\Routing\RouteCollection;

interface CacheInterface
{
    /**
     * storeRouteCollection
     *
     * @param RouteCollection $routeCollection
     *
     * @return void
     */
    public function storeRouteCollection(RouteCollection $routeCollection);

    /**
     * getRoute
     *
     * @param string $name
     *
     * @return void
     */
    public function getRoute($name);

    /**
     * @param $name
     * @return mixed
     */
    public function getRoutes($name);
}
