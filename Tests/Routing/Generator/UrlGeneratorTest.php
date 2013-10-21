<?php

namespace Blablacar\I18nRoutingBundle\Tests\Routing\Generator;

use Blablacar\I18nRoutingBundle\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;

class UrlGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerate_withUnknownRoute_throwRoutNotFoundException()
    {
        $urlGenerator = new UrlGenerator(new RequestContext(), null);
        $urlGenerator->generate('foobar');
    }
}
