<?php

namespace Blablacar\I18nRoutingBundle\Routing;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for Locale Resolvers.
 *
 * A resolver implementation is triggered only if we match a route that is
 * available for multiple locales.
 */
interface LocaleResolverInterface
{
    /**
     * Resolves the locale in case a route is available for multiple locales.
     *
     * @param Request $request
     *
     * @return string|null return the locale guessed from request or null if nothing is found
     */
    public function resolveLocale(Request $request);
}
