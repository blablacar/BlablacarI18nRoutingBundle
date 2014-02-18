<?php

namespace Blablacar\I18nRoutingBundle\Security\Http;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Http\HttpUtils as BaseHttpUtils;
use Blablacar\I18nRoutingBundle\Routing\Loader\I18nLoader;

class HttpUtils extends BaseHttpUtils
{
    private $urlMatcher;

    /**
     * Constructor.
     *
     * @param UrlGeneratorInterface                       $urlGenerator A UrlGeneratorInterface instance
     * @param UrlMatcherInterface|RequestMatcherInterface $urlMatcher   The URL or Request matcher
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(UrlGeneratorInterface $urlGenerator = null, $urlMatcher = null)
    {
        parent::__construct($urlGenerator, $urlMatcher);

        $this->urlMatcher = $urlMatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function checkRequestPath(Request $request, $path)
    {
        if ('/' !== $path[0]) {
            try {
                // matching a request is more powerful than matching a URL path + context, so try that first
                if ($this->urlMatcher instanceof RequestMatcherInterface) {
                    $parameters = $this->urlMatcher->matchRequest($request);
                } else {
                    $parameters = $this->urlMatcher->match($request->getPathInfo());
                }

                if (false === $pos = strpos($parameters['_route'], I18nLoader::ROUTING_PREFIX)) {
                    return $path === $parameters['_route'];
                }

                return $path === substr($parameters['_route'], $pos + strlen(I18nLoader::ROUTING_PREFIX));
            } catch (MethodNotAllowedException $e) {
                return false;
            } catch (ResourceNotFoundException $e) {
                return false;
            }
        }

        return $path === rawurldecode($request->getPathInfo());
    }
}
