<?php

namespace Routing;

use Prophecy\Argument;
use Blablacar\I18nRoutingBundle\Routing\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_is_initializable()
    {
        $container = $this->prophesize('Symfony\Component\DependencyInjection\ContainerInterface');

        $router = new Router(
            $container->reveal(),
            'foobar'
        );

        $this->assertInstanceOf('Blablacar\I18nRoutingBundle\Routing\Router', $router);
    }
}
