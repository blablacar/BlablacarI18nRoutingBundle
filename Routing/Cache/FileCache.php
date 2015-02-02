<?php

namespace Blablacar\I18nRoutingBundle\Routing\Cache;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class FileCache implements CacheInterface
{
    /**
     * Cache path
     *
     * @var string
     */
    protected $cachePath;

    /**
     * Cached routes
     *
     * @var array
     */
    protected $cachedRoutes = array();

    /**
     * @var Route[]
     */
    protected $unserializedRoutes = [];

    /**
     * @param string $cacheDir
     */
    public function __construct($cacheDir)
    {
        $this->cachePath = $cacheDir . DIRECTORY_SEPARATOR . 'routing';
    }

    /**
     * {@inheritDoc}
     */
    public function storeRouteCollection(RouteCollection $routeCollection)
    {
        // we need the directory no matter the proxy cache generation strategy
        if (!is_dir($this->cachePath)) {
            if (false === @mkdir($this->cachePath, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create the Router directory "%s".', $this->cachePath));
            }
        } elseif (!is_writable($this->cachePath)) {
            throw new \RuntimeException(sprintf('The Router directory "%s" is not writeable for the current system user.', $this->cachePath));
        }

        $routeCollections = $this->splitRouteCollection($routeCollection);

        foreach ($routeCollections as $prefix => $routeCollection) {
            $routes = $this->compileRouteCollection($routeCollection);
            file_put_contents($this->cachePath.'/'.$prefix.'.php', $routes);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRoute($name)
    {
        $prefix = $this->getPrefix($name);

        if (isset($this->unserializedRoutes[$prefix]) && isset($this->unserializedRoutes[$prefix][$name])) {
            return $this->unserializedRoutes[$prefix][$name];
        }

        $this->loadPrefix($prefix);

        if (!array_key_exists($name, $this->cachedRoutes[$prefix])) {
            throw new RouteNotFoundException(sprintf(
                'Unable to generate a URL for the named route "%s" as such route does not exist.',
                $name
            ));
        }

        $data = $this->cachedRoutes[$prefix][$name];
        $route = new Route(null);
        $route->unserialize($data);

        return $this->unserializedRoutes[$prefix][$name] = $route;
    }

    public function getRoutes($name)
    {
        $prefix = $this->getPrefix($name);
        $this->loadPrefix($prefix);

        return array_map(function($data) use ($prefix, $name) {
            $route = new Route(null);
            $route->unserialize($data);

            return $this->unserializedRoutes[$prefix][$name] = $route;
        }, $this->cachedRoutes[$prefix]);
    }

    protected function loadPrefix($prefix)
    {
        if (array_key_exists($prefix, $this->cachedRoutes)) {
            return;
        }

        if (!file_exists($cacheFile = $this->cachePath . DIRECTORY_SEPARATOR . $prefix . '.php')) {
            throw new RouteNotFoundException(sprintf(
                'The cache file with name "%s" was not found',
                $cacheFile
            ));
        }
        
        $this->cachedRoutes[$prefix] = require $cacheFile;
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
