<?php

namespace Blablacar\I18nRoutingBundle\Routing\Generator;

use Blablacar\I18nRoutingBundle\Routing\Cache\CacheInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGenerator as BaseUrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class UrlGenerator extends BaseUrlGenerator
{
    /**
     * Cache
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @param RequestContext  $context
     * @param CacheInterface  $cache
     * @param LoggerInterface $logger
     */
    public function __construct(RequestContext $context, CacheInterface $cache, LoggerInterface $logger = null)
    {
        parent::__construct(new RouteCollection(), $context, $logger);

        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        $route = $this->cache->getRoute($name);
        $compiledRoute = $route->compile();

        return $this->doGenerate(
            $compiledRoute->getVariables(),
            $route->getDefaults(),
            $route->getRequirements(),
            $compiledRoute->getTokens(),
            $parameters,
            $name,
            $referenceType,
            $compiledRoute->getHostTokens()
        );
    }
}
