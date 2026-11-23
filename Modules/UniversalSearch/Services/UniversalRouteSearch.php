<?php

declare(strict_types=1);

namespace Modules\UniversalSearch\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UniversalRouteSearch
{
    public function __construct(private readonly Router $router)
    {
    }

    /**
     * @return array<int, array{title:string, url:string, name:string, uri:string, group:string|null}>
     */
    public function search(?Authenticatable $user, string $query): array
    {
        $query = trim($query);

        $min = (int) config('universalsearch.min_query_length', 2);
        if (mb_strlen($query) < $min) {
            return [];
        }

        $candidates = $this->getCandidateRoutes();

        $labels = (array) config('universalsearch.labels', []);
        $max = (int) config('universalsearch.max_results', 15);

        $terms = $this->expandWithSynonyms(mb_strtolower($query));
        $results = [];
        $seen = [];

        foreach ($candidates as $route) {
            if (!$this->routeIsAllowedForUser($route, $user)) {
                continue;
            }

            $name = (string) $route->getName();
            if (isset($seen[$name])) {
                continue;
            }

            $uri = (string) $route->uri();

            $label = Arr::get($labels, $name, []);
            $title = (string) (Arr::get($label, 'title') ?: $name);
            $group = Arr::get($label, 'group');

            $haystack = mb_strtolower($title . ' ' . $name . ' ' . $uri . ' ' . ($group ?? ''));

            if (!$this->haystackMatchesAnyTerm($haystack, $terms)) {
                continue;
            }

            $url = $this->safeUrlForRouteName($name);
            if ($url === null) {
                continue;
            }

            $seen[$name] = true;
            $results[] = [
                'title' => $title,
                'url'   => $url,
                'name'  => $name,
                'uri'   => $uri,
                'group' => is_string($group) ? $group : null,
                'score' => $this->scoreResult($haystack, $terms, mb_strtolower($query)),
            ];
        }

        // Sort by relevance score descending
        usort($results, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        // Strip score from output and limit
        return array_map(
            static fn (array $r) => Arr::except($r, ['score']),
            array_slice($results, 0, $max)
        );
    }

    /**
     * @return array<int, IlluminateRoute>
     */
    private function getCandidateRoutes(): array
    {
        $excludeNamePrefixes = (array) config('universalsearch.exclude_name_prefixes', []);
        $excludeUriPrefixes = (array) config('universalsearch.exclude_uri_prefixes', []);

        $routes = [];
        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            if (!$route instanceof IlluminateRoute) {
                continue;
            }

            $name = $route->getName();
            if (!$name) {
                continue;
            }

            if (!$this->isGetLike($route)) {
                continue;
            }

            if (!$this->hasNoRequiredParameters($route)) {
                continue;
            }

            $uri = $route->uri();
            if (!$this->passesExcludes((string) $name, (string) $uri, $excludeNamePrefixes, $excludeUriPrefixes)) {
                continue;
            }

            $routes[] = $route;
        }

        return $routes;
    }

    private function passesExcludes(string $name, string $uri, array $excludeNamePrefixes, array $excludeUriPrefixes): bool
    {
        foreach ($excludeNamePrefixes as $prefix) {
            if ($prefix !== '' && Str::startsWith($name, (string) $prefix)) {
                return false;
            }
        }

        foreach ($excludeUriPrefixes as $prefix) {
            $prefix = (string) $prefix;
            if ($prefix === '') {
                continue;
            }

            if (Str::startsWith($uri, $prefix) || Str::startsWith('/' . $uri, $prefix)) {
                return false;
            }
        }

        return true;
    }

    private function isGetLike(IlluminateRoute $route): bool
    {
        $methods = $route->methods();
        return in_array('GET', $methods, true) || in_array('HEAD', $methods, true);
    }

    private function hasNoRequiredParameters(IlluminateRoute $route): bool
    {
        // If the route has parameters, route() will require them.
        // We skip those to avoid generating broken URLs in search results.
        return count($route->parameterNames()) === 0;
    }

    private function safeUrlForRouteName(string $name): ?string
    {
        try {
            return route($name);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Expand a query string into a list of terms including synonyms.
     *
     * @return array<int, string>
     */
    private function expandWithSynonyms(string $query): array
    {
        $synonymGroups = (array) config('universalsearch.synonyms', []);
        $terms = [$query];

        foreach ($synonymGroups as $group) {
            $group = array_map('mb_strtolower', (array) $group);
            foreach ($group as $word) {
                if (Str::contains($query, $word)) {
                    // Add all other words in the group as extra search terms
                    foreach ($group as $synonym) {
                        if ($synonym !== $word && !in_array($synonym, $terms, true)) {
                            $terms[] = $synonym;
                        }
                    }
                    break;
                }
            }
        }

        return $terms;
    }

    /**
     * Check if haystack matches any of the given terms.
     *
     * @param array<int, string> $terms
     */
    private function haystackMatchesAnyTerm(string $haystack, array $terms): bool
    {
        foreach ($terms as $term) {
            if (Str::contains($haystack, $term)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Score a result: higher = more relevant.
     * Exact query match in title scores highest, synonym matches score lower.
     *
     * @param array<int, string> $terms
     */
    private function scoreResult(string $haystack, array $terms, string $originalQuery): int
    {
        $score = 0;

        // Exact original query match in haystack = highest score
        if (Str::contains($haystack, $originalQuery)) {
            $score += 100;
        }

        // Each synonym term that matches adds points
        foreach ($terms as $i => $term) {
            if ($term !== $originalQuery && Str::contains($haystack, $term)) {
                // Earlier synonyms (closer to original) score higher
                $score += max(10, 50 - ($i * 5));
            }
        }

        return $score;
    }

    private function routeIsAllowedForUser(IlluminateRoute $route, ?Authenticatable $user): bool
    {
        $middlewares = $route->gatherMiddleware();

        // Route requires auth but user is missing
        if (in_array('auth', $middlewares, true) && !$user) {
            return false;
        }

        $authorizableUser = $user instanceof \Illuminate\Contracts\Auth\Access\Authorizable ? $user : null;

        // Handle: can:permission-name
        foreach ($middlewares as $mw) {
            if (is_string($mw) && Str::startsWith($mw, 'can:')) {
                $ability = trim((string) Str::after($mw, 'can:'));
                if ($ability !== '' && $authorizableUser) {
                    if (!$authorizableUser->can($ability)) {
                        return false;
                    }
                }
            }

            // Handle Spatie: permission:xyz
            if (is_string($mw) && Str::startsWith($mw, 'permission:')) {
                $permission = trim((string) Str::after($mw, 'permission:'));
                if ($permission !== '' && $authorizableUser) {
                    if (!$authorizableUser->can($permission)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}

