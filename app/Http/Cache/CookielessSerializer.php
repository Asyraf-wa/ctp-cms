<?php

namespace App\Http\Cache;

use Spatie\ResponseCache\Serializers\JsonSerializer;
use Symfony\Component\HttpFoundation\Response;

/**
 * The stock serializer keeps every header, including Set-Cookie: the first visitor's session cookie
 * would be replayed to everyone. Cookies are dropped here; each request still gets its own from the
 * session middleware, which runs outside the cache.
 */
class CookielessSerializer extends JsonSerializer
{
    public function serialize(Response $response): string
    {
        $copy = clone $response;
        $copy->headers = clone $response->headers;
        foreach ($copy->headers->getCookies() as $cookie) {
            $copy->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
        }
        $copy->headers->remove('Set-Cookie');

        return parent::serialize($copy);
    }
}
