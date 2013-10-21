<?php

namespace Blablacar\I18nRoutingBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Blablacar\I18nRoutingBundle\DependencyInjection\Compiler\RouterCompilerPass;

class BlablacarI18nRoutingBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RouterCompilerPass());
    }
}
