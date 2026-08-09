<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Support;

final class Endpoint
{
    public static function identity(string $url): string
    {
        return self::rebuild($url, sanitize: false) ?? trim($url);
    }

    public static function provenance(string $url): string
    {
        return self::rebuild($url, sanitize: true) ?? '[redacted-endpoint]';
    }

    private static function rebuild(string $url, bool $sanitize): ?string
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $host = str_contains($host, ':') ? "[{$host}]" : $host;
        $port = isset($parts['port']) && ! (($scheme === 'https' && $parts['port'] === 443) || ($scheme === 'http' && $parts['port'] === 80))
            ? ':'.$parts['port']
            : '';
        $userinfo = '';

        if (! $sanitize && isset($parts['user'])) {
            $userinfo = $parts['user'];

            if (isset($parts['pass'])) {
                $userinfo .= ':'.$parts['pass'];
            }

            $userinfo .= '@';
        }

        $path = $parts['path'] ?? '';
        $query = isset($parts['query'])
            ? '?'.($sanitize ? self::sanitizedQuery($parts['query']) : $parts['query'])
            : '';

        return "{$scheme}://{$userinfo}{$host}{$port}{$path}{$query}";
    }

    private static function sanitizedQuery(string $query): string
    {
        $parameters = [];

        foreach (explode('&', $query) as $parameter) {
            $parts = explode('=', $parameter, 2);
            $key = $parts[0];
            $value = $parts[1] ?? null;

            if (self::isSensitiveQueryKey(rawurldecode($key))) {
                $parameters[] = $key.'=%5BREDACTED%5D';

                continue;
            }

            $parameters[] = $value === null ? $key : $key.'='.$value;
        }

        return implode('&', $parameters);
    }

    private static function isSensitiveQueryKey(string $key): bool
    {
        $key = strtolower(str_replace(['[', ']'], ['.', ''], $key));

        return preg_match('/(?:^|[._-])(api[-_]?key|access[-_]?token|token|key|signature|credential|password|secret|auth|authorization)(?:$|[._-])/', $key) === 1;
    }
}
