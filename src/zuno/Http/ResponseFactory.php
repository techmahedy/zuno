<?php

namespace Zuno\Http;

use Zuno\Support\Collection;
use Zuno\Http\Response\JsonResponse;
use Zuno\Database\Eloquent\Model;
use Zuno\Database\Eloquent\Builder;

class ResponseFactory
{
    /**
     * Create a new response instance.
     *
     * @param mixed $content
     * @param int $status
     * @param array $headers
     * @return \Zuno\Http\Response
     */
    public function make($content = '', $status = 200, array $headers = []): Response
    {
        $response = app(Response::class);
        $response->setStatusCode($status);
        if ($content instanceof Collection) {
            $content = json_encode($content->toArray());
            $response->headers->set('Content-Type', 'application/json');
        } elseif (is_array($content)) {
            $content = json_encode($content);
            $response->headers->set('Content-Type', 'application/json');
        } elseif ($content instanceof Model) {
            $content = json_encode($content);
            $response->headers->set('Content-Type', 'application/json');
        } elseif ($content instanceof Builder) {
            $content = json_encode($content);
            $response->headers->set('Content-Type', 'application/json');
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }
        $response->setBody($content);

        return $response;
    }

    /**
     * Return a JSON response.
     *
     * This method sets the appropriate headers for a JSON response and returns the JSON-encoded data.
     *
     * @param mixed $data The data to encode as JSON.
     * @param int $statusCode The HTTP status code (default: 200).
     * @param array<string, string> $headers Additional headers to include in the response.
     * @param array $headers
     * @return JsonResponse
     */
    public function json(mixed $data, int $statusCode = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $statusCode, $headers);
    }

    /**
     * Return a plain text response.
     *
     * @param string $content The plain text content.
     * @param int $statusCode The HTTP status code (default: 200).
     * @param array<string, string> $headers Additional headers to include in the response.
     * @return Response
     */
    public function text(string $content, int $statusCode = 200, array $headers = []): Response
    {
        return app(Response::class)->text($content, $statusCode, $headers);
    }
}
