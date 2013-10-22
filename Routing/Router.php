<?php

namespace Blablacar\I18nRoutingBundle\Routing;

use JMS\I18nRoutingBundle\Router\I18nRouter as BaseI18nRouter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Config\ConfigCache;

class Router extends BaseI18nRouter
{
    public function setGenerator(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    public function getGenerator()
    {
        return $this->generator;
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
            return $this->matcher = new $this->options['matcher_class']($this->getCleanRouteCollection(), $this->context);
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
        $routeCollectionCleaner = new RouteCollectionCleaner();

        return $routeCollectionCleaner->clean($this->getRouteCollection());
    }
}

