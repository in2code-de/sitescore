<?php

declare(strict_types=1);

namespace In2code\Sitescore\Utility;

use Psr\Http\Message\RequestInterface;

class UrlUtility
{
    public static function makeAbsoluteWithCurrentDomain(string $url, RequestInterface $request): string
    {
        if (preg_match('~^https?://~', $url) === 0) {
            $baseUri = $request->getAttribute('normalizedParams')?->getSiteUrl()
                ?? (string)$request->getUri()->withPath('')->withQuery('')->withFragment('');
            $url = rtrim($baseUri, '/') . '/' . ltrim($url, '/');
        }
        return $url;
    }
}
