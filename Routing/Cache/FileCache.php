<?php

namespace Blablacar\I18nRoutingBundle\Routing\Cache;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class FileCache implements CacheInterface
{
    protected $cacheDir;

    protected $cachedRoutes = array();

    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir . DIRECTORY_SEPARATOR . 'routing';
    }

    /**
     * {@inheritDoc}
     */
    public function storeRouteCollection(RouteCollection $routeCollection)
    {
        // we need the directory no matter the proxy cache generation strategy
        if (!is_dir($this->cacheDir)) {
            if (false === @mkdir($this->cacheDir, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create the Router directory "%s".', $this->cacheDir));
            }
        } elseif (!is_writable($this->cacheDir)) {
            throw new \RuntimeException(sprintf('The Router directory "%s" is not writeable for the current system user.', $this->cacheDir));
        }

        $routeCollections = $this->splitRouteCollection($routeCollection);

        foreach ($routeCollections as $prefix => $routeCollection) {
            $routes = $this->compileRouteCollection($routeCollection);
            file_put_contents($this->cacheDir.'/'.$prefix.'.php', $routes);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRoute($name)
    {
        $prefix = $this->getPrefix($name);
        if (!array_key_exists($prefix, $this->cachedRoutes)) {
            $cacheFile = $this->cacheDir . DIRECTORY_SEPARATOR . $prefix . '.php';
            if (! file_exists($cacheFile)) {
                throw new RouteNotFoundException(sprintf(
                    'Unable to generate a URL for the named route "%s" as such route does not exist (The cache file with name "%s" was not found',
                    $name,
                    $cacheFile
                ));
            }
            $this->cachedRoutes[$prefix] = require $cacheFile;
        }

        if (!array_key_exists($name, $this->cachedRoutes[$prefix])) {
            throw new RouteNotFoundException(sprintf(
                'Unable to generate a URL for the named route "%s" as such route does not exist.',
                $name
            ));
        }

        $data = $this->cachedRoutes[$prefix][$name];
        $route = new Route(null);
        $route->unserialize($data);

        return $route;
    }

    /**
     * splitRouteCollection
     *
     * @param RouteCollection $routeCollection
     *
     * @return RouteCollection[]
     */
    protected function splitRouteCollection(RouteCollection $routeCollection)
    {
        $routeCollections = array();
        foreach ($routeCollection as $name => $route) {
            $prefix = $this->getPrefix($name);
            if (!array_key_exists($prefix, $routeCollections)) {
                $routeCollections[$prefix] = new RouteCollection();
            }
            $routeCollections[$prefix]->add($name, $route);
        }

        return $routeCollections;
    }

    /**
     * compileRouteCollection
     *
     * @param RouteCollection $routeCollection
     *
     * @return void
     */
    protected function compileRouteCollection(RouteCollection $routeCollection)
    {
        $routes = "<?php\nreturn array(\n";
        foreach ($routeCollection as $name => $route) {
            $routes .= sprintf("    '%s' => '%s',\n", $name, $route->serialize());
        }
        $routes .= ');';

        return $routes;
    }

    /**
     * getPrefix
     *
     * @param string $name
     *
     * @return string
     */
    protected function getPrefix($name)
    {
        return substr($name, 0, 5);
    }
}
