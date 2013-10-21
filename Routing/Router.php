<?php

namespace Blablacar\I18nRoutingBundle\Routing;

use Symfony\Component\Routing\Router as BaseRouter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Router extends BaseRouter
{
    public function setGenerator(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * {@inheritDoc}
     */
    public function getGenerator()
    {
        return $this->generator;
    }
}

