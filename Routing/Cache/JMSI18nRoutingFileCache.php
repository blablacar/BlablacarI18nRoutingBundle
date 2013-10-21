<?php

namespace Blablacar\I18nRoutingBundle\Routing\Cache;

use JMS\I18nRoutingBundle\Router\I18nLoader;

class JMSI18nRoutingFileCache extends FileCache
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
