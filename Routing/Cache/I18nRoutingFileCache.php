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
        if (preg_match('#(.*)'.I18nLoader::ROUTING_PREFIX.'.*#', $name, $matches)) {
            return $matches[1];
        }

        return parent::getPrefix($name);
    }
}
