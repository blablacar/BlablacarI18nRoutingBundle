<?php

namespace Blablacar\I18nRoutingBundle;

use Blablacar\I18nRoutingBundle\DependencyInjection\Compiler\SetRouterPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BlablacarI18nRoutingBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SetRouterPass());
    }
}
