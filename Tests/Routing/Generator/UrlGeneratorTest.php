<?php

namespace Blablacar\I18nRoutingBundle\Tests\Routing\Generator;

use Blablacar\I18nRoutingBundle\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Blablacar\I18nRoutingBundle\Routing\Cache\FileCache;

class UrlGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerate_withUnknownRoute_throwRoutNotFoundException()
    {
        $urlGenerator = new UrlGenerator(new RequestContext(), new FileCache(sys_get_temp_dir()));
        $urlGenerator->generate('foobar');
    }
}
