<?php

namespace Zuno\Http\Support;

trait RequestHelper
{
    /**
     * Stores validation passed data.
     *
     * @var array<string, mixed>
     */
    public array $passedData = [];

    /**
     * Stores validation errors.
     *
     * @var array<string, mixed>
     */
    public array $errors = [];

    /**
     * Retrieves all input data except for the specified keys.
     *
     * @param array|string $keys The keys to exclude.
     * @return array<string, mixed> The filtered input data.
     */
    public function except(array|string $keys): array
    {
        if (is_string($keys)) {
            $keys = [$keys];
        }

        return array_diff_key($this->all(), array_flip($keys));
    }

    /**
     * Retrieves only the specified keys from the input data.
     *
     * @param array|string $keys The keys to include.
     * @return array<string, mixed> The filtered input data.
     */
    public function only(array|string $keys): array
    {
        if (is_string($keys)) {
            $keys = [$keys];
        }

        return array_intersect_key($this->all(), array_flip($keys));
    }

    /**
     * Retrieves the validation passed data, excluding specified fields.
     *
     * @param array<string> $excludeKeys The keys to exclude (e.g., ['csrf_token', 'other_field']).
     * @return array<string, mixed> The validation passed data without the excluded fields.
     */
    public function passed(array $excludeKeys = ['csrf_token']): array
    {
        $exclude = array_flip($excludeKeys);

        return array_diff_key($this->passedData, $exclude);
    }

    /**
     * Retrieves the validation errors, excluding the `csrf_token` field.
     *
     * @return array<string, mixed> The validation errors without `csrf_token`.
     */
    public function failed(array $excludeKeys = ['csrf_token']): array
    {
        return $this->errors ?? [];
    }

    /**
     * Sets the validation passed data.
     *
     * @param array<string, mixed> $data The validation passed data.
     * @return self The current instance.
     */
    public function setPassedData(array $data): self
    {
        $this->passedData = $data;

        return $this;
    }

    /**
     * Sets the validation errors.
     *
     * @param array<string, mixed> $errors The validation errors.
     * @return self The current instance.
     */
    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Checks if the input data is empty.
     *
     * @return bool True if the input data is empty, false otherwise.
     */
    public function isEmpty(): bool
    {
        return empty($this->all());
    }


    /**
     * Retrieves all input data, sanitizing it based on the request method.
     *
     * @return array<string, mixed> Sanitized input data.
     */
    public function all(): array
    {
        $body = [];
        $inputSource = $this->getMethod() === 'get' ? $_GET : $_POST;

        foreach ($inputSource as $key => $value) {
            $body[$key] = filter_input(
                $this->getMethod() === 'get' ? INPUT_GET : INPUT_POST,
                $key,
                FILTER_SANITIZE_SPECIAL_CHARS
            );
        }

        return $body;
    }

    /**
     * Retrieves a specific input parameter or all input data.
     *
     * @param string $param The parameter to retrieve.
     * @return mixed The input value if the parameter exists, otherwise the whole input array.
     */
    public function input(string $param): mixed
    {
        if (empty($this->input)) {
            $this->input = $this->all();
        }

        return $this->input[$param] ?? null;
    }

    /**
     * Checks if a specific parameter exists in the input data.
     *
     * @param string $param The parameter to check.
     * @return bool True if the parameter exists, false otherwise.
     */
    public function has(string $param): bool
    {
        if (empty($this->input)) {
            $this->input = $this->all();
        }

        return array_key_exists($param, $this->input);
    }
}
