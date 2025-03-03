<?php

namespace Zuno\Http;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Response
{
    /**
     * Return a new JSON response from the application.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $encodingOptions
     * @return JsonResponse
     */
    public static function json(
        mixed $data = [],
        int $status = 200,
        array $headers = [],
        int $encodingOptions = 0
    ): JsonResponse {
        return new JsonResponse($data, $status, $headers, $encodingOptions);
    }

    /**
     * Return a new streamed response from the application.
     *
     * @param callable $callback
     * @param int $status
     * @param array $headers
     * @return StreamedResponse
     */
    public static function stream(callable $callback, int $status = 200, array $headers = []): StreamedResponse
    {
        return new StreamedResponse($callback, $status, $headers);
    }

    /**
     * Return a new streamed download response from the application.
     *
     * @param callable $callback
     * @param string $filename
     * @param array $headers
     * @param string|null $disposition
     * @return StreamedResponse
     */
    public static function streamDownload(
        callable $callback,
        string $filename,
        array $headers = [],
        string $disposition = 'attachment'
    ): StreamedResponse {
        $response = new StreamedResponse($callback, 200, $headers);

        $response->headers->set('Content-Disposition', $response->headers->makeDisposition($disposition, $filename));

        return $response;
    }

    /**
     * Create a new file download response.
     *
     * @param \SplFileInfo|string $file
     * @param string|null $name
     * @param array $headers
     * @param string|null $disposition
     * @return BinaryFileResponse
     */
    public static function download(
        \SplFileInfo|string $file,
        string $name = null,
        array $headers = [],
        string $disposition = 'attachment'
    ): BinaryFileResponse {
        $response = new BinaryFileResponse($file, 200, $headers, true, $disposition);

        if (!is_null($name)) {
            $response->setContentDisposition($disposition, $name);
        }

        return $response;
    }

    /**
     * Create a new redirect response to the given path.
     *
     * @param string $path
     * @param int $status
     * @param array $headers
     * @return RedirectResponse
     */
    public static function redirect(string $path, int $status = 302, array $headers = []): RedirectResponse
    {
        return new RedirectResponse($path, $status, $headers);
    }

    /**
     * Create a new redirect response to the given URL.
     *
     * @param string $url
     * @param int $status
     * @param array $headers
     * @return RedirectResponse
     */
    public static function redirectUrl(string $url, int $status = 302, array $headers = []): RedirectResponse
    {
        return static::redirect($url, $status, $headers);
    }

    /**
     * Create a new redirect response back to the previous location.
     *
     * @param int $status
     * @param array $headers
     * @return RedirectResponse
     */
    public static function redirectBack(int $status = 302, array $headers = []): RedirectResponse
    {
        return static::redirect($_SERVER['HTTP_REFERER'] ?? '/', $status, $headers);
    }

    /**
     * Set the content type header.
     *
     * @param string $contentType
     * @return SymfonyResponse
     */
    public function contentType(string $contentType): SymfonyResponse
    {
        $this->headers->set('Content-Type', $contentType);

        return $this;
    }

    /**
     * Get the Symfony Response Header Bag instance.
     *
     * @return ResponseHeaderBag
     */
    public function headers(): ResponseHeaderBag
    {
        return $this->headers;
    }
}
