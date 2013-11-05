<?php

namespace Blablacar\I18nRoutingBundle\Routing\Cache;

use Blablacar\I18nRoutingBundle\Routing\Loader\I18nLoader;

class I18nRoutingFileCache extends FileCache
{
    /**
     * {@inheritDoc}
     */
    protected function getPrefix($name)
    {
        $parts = explode(I18nLoader::ROUTING_PREFIX, $name, 2);

        if (count($parts) > 1) {
            return array_shift($parts);
        }

        return parent::getPrefix($name);
    }
}
