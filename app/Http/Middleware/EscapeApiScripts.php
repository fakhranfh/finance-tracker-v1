<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EscapeApiScripts
{
    /**
     * HTML-escape any string in a JSON response that contains a <script> tag.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->setData($this->escape($response->getData(true)));
            $response->setEncodingOptions($response->getEncodingOptions() | JSON_HEX_TAG);
        }

        return $response;
    }

    protected function escape(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->escape($item), $value);
        }

        if (is_string($value) && preg_match('/<\s*\/?\s*script/i', $value)) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
        }

        return $value;
    }
}
