<?php

namespace Blablacar\I18nRoutingBundle\Routing;

use Symfony\Component\HttpFoundation\Request;

/**
 * Default Locale Resolver.
 */
class DefaultLocaleResolver implements LocaleResolverInterface
{
    private $hostMap;

    public function __construct(array $hostMap = array())
    {
        $this->hostMap = $hostMap;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveLocale(Request $request)
    {
        if ($this->hostMap && isset($this->hostMap[$host = $request->getHost()])) {
            return $this->hostMap[$host];
        }

        return null;
    }
}
