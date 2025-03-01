<?php

namespace Zuno;

class File
{
    // To hold the file data (e.g., name, type, size, etc.)
    protected array $file;

    /**
     * Constructor that initializes the File object with the given file data.
     *
     * @param array $file The uploaded file's data from the $_FILES array.
     */
    public function __construct(array $file)
    {
        $this->file = $file;
    }

    /**
     * Gets the original name of the uploaded file.
     *
     * @return string The name of the file.
     */
    public function getClientOriginalName(): string
    {
        return $this->file['name'] ?? '';
    }

    /**
     * Gets the temporary path where the file is stored on the server.
     *
     * @return string The temporary file path.
     */
    public function getClientOriginalPath(): string
    {
        return $this->file['tmp_name'] ?? '';
    }

    /**
     * Gets the MIME type of the uploaded file.
     *
     * @return string The MIME type of the file (e.g., "image/jpeg").
     */
    public function getClientOriginalType(): string
    {
        return $this->file['type'] ?? '';
    }

    /**
     * Gets the size of the uploaded file in bytes.
     *
     * @return int The file size in bytes.
     */
    public function getSize(): int
    {
        return $this->file['size'] ?? 0;
    }

    /**
     * Gets the error code of the uploaded file.
     *
     * @return int The error code of the file upload process.
     */
    public function getError(): int
    {
        return $this->file['error'] ?? 0;
    }

    /**
     * Checks if the file was uploaded successfully.
     *
     * @return bool True if the file upload was successful, otherwise false.
     */
    public function isValid(): bool
    {
        return $this->getError() === UPLOAD_ERR_OK;
    }
}
