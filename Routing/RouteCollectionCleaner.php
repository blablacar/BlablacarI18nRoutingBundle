<?php

namespace Blablacar\I18nRoutingBundle\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteCollectionCleaner
{
    /**
     * clean
     *
     * @param RouteCollection $routeCollection
     *
     * @return RouteCollection
     */
    public function clean(RouteCollection $routeCollection)
    {
        $routesByPath = array();
        foreach ($routeCollection as $name => $route) {
            $routesByPath[$this->getUniqueKeyForRoute($route)][] = array(
                'name'  => $name,
                'route' => $route,
            );
        }

        $routeCollection = new RouteCollection();
        foreach ($routesByPath as $path => $routes) {
            $route = $this->chooseBestRoute($routes);
            $routeCollection->add($route['name'], $route['route']);
        }

        return $routeCollection;
    }

    /**
     * getUniqueKeyForRoute
     *
     * @param Route $route
     *
     * @return string
     */
    protected function getUniqueKeyForRoute(Route $route)
    {
        return sprintf(
            '%s-%s',
            implode('', $route->getMethods()),
            $route->getPath()
        );
    }

    /**
     * chooseBestRoute
     *
     * @param array $routes
     *
     * @return array
     */
    protected function chooseBestRoute(array $routes)
    {
        return reset($routes);
    }
}
