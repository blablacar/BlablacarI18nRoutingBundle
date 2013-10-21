<?php

namespace Blablacar\I18nRoutingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class RouterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('router.default')) {
            return;
        }
        if (!$container->getDefinition('blablacar_optimized_routing.generator')) {
            return;
        }

        $container
            ->getDefinition('router.default')
            ->addMethodCall(
                'setGenerator',
                array($container->getDefinition('blablacar_optimized_routing.generator'))
            );
    }
}
