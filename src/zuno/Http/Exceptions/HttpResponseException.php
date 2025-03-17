<?php

namespace Zuno\Http\Exceptions;

use RuntimeException;
use Zuno\Http\Response;
use Throwable;

class HttpResponseException extends RuntimeException
{
    /**
     * The underlying response instance.
     *
     * @var \Zuno\Http\Response
     */
    protected $response;

    /**
     * The validation errors.
     *
     * @var mixed
     */
    protected $validationErrors;

    /**
     * The HTTP status code.
     *
     * @var int
     */
    protected $statusCode;

    /**
     * Create a new HTTP response exception instance.
     *
     * @param \Zuno\Http\Response $response
     * @param mixed $validationErrors
     * @param int $statusCode
     * @param \Throwable|null $previous
     * @return void
     */
    public function __construct(
        Response $response,
        mixed $validationErrors = null,
        int $statusCode = 500,
        ?Throwable $previous = null
    ) {
        parent::__construct($previous?->getMessage() ?? '', $previous?->getCode() ?? 0, $previous);

        $this->response = $response;
        $this->validationErrors = $validationErrors;
        $this->statusCode = $statusCode;
    }

    /**
     * Get the underlying response instance.
     *
     * @return \Zuno\Http\Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Get the validation errors.
     *
     * @return mixed
     */
    public function getValidationErrors(): mixed
    {
        return $this->validationErrors;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
