<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Routing;

use PHPCR\Util\PathHelper;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides custom-url routes.
 */
class CustomUrlRouteProvider implements RouteProviderInterface
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var PathBuilder
     */
    private $pathBuilder;

    /**
     * @var string
     */
    private $environment;

    public function __construct(RequestAnalyzerInterface $requestAnalyzer, PathBuilder $pathBuilder, $environment)
    {
        $this->requestAnalyzer = $requestAnalyzer;
        $this->pathBuilder = $pathBuilder;
        $this->environment = $environment;
    }

    public function getRouteCollectionForRequest(Request $request)
    {
        $collection = new RouteCollection();

        $routeDocument = $this->requestAnalyzer->getAttribute('customUrlRoute');
        $customUrlDocument = $this->requestAnalyzer->getAttribute('customUrl');
        $localization = $this->requestAnalyzer->getAttribute('localization');
        if (null === $routeDocument || null === $localization) {
            return $collection;
        }

        if ($routeDocument->isHistory()) {
            // if custom-url is not redirect to avoid double redirects.
            if (!$routeDocument->getTargetDocument()->getTargetDocument()->isRedirect()) {
                return $this->addHistoryRedirectToRouteCollection(
                    $request,
                    $routeDocument,
                    $collection,
                    $this->requestAnalyzer->getWebspace()->getKey()
                );
            }

            $routeDocument = $routeDocument->getTargetDocument();
            $customUrlDocument = $routeDocument->getTargetDocument();
        }

        if (null === $customUrlDocument
            || false === $customUrlDocument->isPublished()
            || (
                null !== $customUrlDocument->getTargetDocument()
                && WorkflowStage::PUBLISHED !== $customUrlDocument->getTargetDocument()->getWorkflowStage()
            )
        ) {
            return $collection;
        }

        $collection->add(
            uniqid('custom_url_route_', true),
            new Route(
                $this->decodePathInfo($request->getPathInfo()),
                [
                    '_custom_url' => $customUrlDocument,
                    '_webspace' => $this->requestAnalyzer->getWebspace(),
                    '_environment' => $this->environment,
                ]
            )
        );

        return $collection;
    }

    public function getRouteByName($name)
    {
        // TODO: Implement getRouteByName() method.
    }

    public function getRoutesByNames($names)
    {
        return [];
    }

    /**
     * Add redirect to current custom-url.
     *
     * @param string $webspaceKey
     *
     * @return RouteCollection
     */
    private function addHistoryRedirectToRouteCollection(
        Request $request,
        RouteDocument $routeDocument,
        RouteCollection $collection,
        $webspaceKey
    ) {
        $resourceSegment = PathHelper::relativizePath(
            $routeDocument->getTargetDocument()->getPath(),
            $this->getRoutesPath($webspaceKey)
        );

        $url = sprintf('%s://%s', $request->getScheme(), $resourceSegment);

        $collection->add(
            uniqid('custom_url_route_', true),
            new Route(
                $this->decodePathInfo($request->getPathInfo()),
                [
                    '_controller' => 'sulu_website.redirect_controller:redirectAction',
                    '_finalized' => true,
                    'url' => $url,
                ]
            )
        );

        return $collection;
    }

    /**
     * Return routes path for custom-url in given webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    private function getRoutesPath($webspaceKey)
    {
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%custom_urls%', '%custom_urls_routes%']);
    }

    /**
     * Server encodes the url and symfony does not encode it
     * Symfony decodes this data here https://github.com/symfony/symfony/blob/3.3/src/Symfony/Component/Routing/Matcher/UrlMatcher.php#L91.
     *
     * @param string $pathInfo
     *
     * @return string
     */
    private function decodePathInfo($pathInfo)
    {
        return rawurldecode($pathInfo);
    }
}
