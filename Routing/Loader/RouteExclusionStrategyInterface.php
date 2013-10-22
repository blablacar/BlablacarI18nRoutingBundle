<?php

namespace Blablacar\I18nRoutingBundle\Routing\Loader;

use Symfony\Component\Routing\Route;

interface RouteExclusionStrategyInterface
{
    /**
     * Implementations determine whether the given route is eligible for i18n.
     *
     * @param string $routeName
     * @param Route $route
     *
     * @return Boolean
     */
    public function shouldExcludeRoute($routeName, Route $route);
}
