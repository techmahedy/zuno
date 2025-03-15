<?php

namespace Zuno\Support\Storage;

class FileSystem
{
    /**
     * Full File path directory without filename
     *
     * @var string
     */
    protected string $filePath;

    /**
     * Custom FileName
     *
     * @var string
     */
    protected string $fileName;

    public function __construct(string $filePath, string $fileName = '')
    {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }
    /**
     * move file in local file system
     *
     * @param string $path
     * @return boolean
     */
    public function put(string $path, $file): bool
    {
        $fileName = $this->getFileName($file);
        $stream   = fopen($file->getClientOriginalPath(), 'rb'); // Read mode (binary)

        if (is_resource($stream)) {
            $this->writeStream($path, $fileName, $stream);
            return true;
        }

        return false;
    }

    /**
     * Undocumented function
     *
     * @param [Zuno/Support/File] $file
     * @return string
     */
    public function getFileName($file): string
    {
        if (is_null($this->fileName) || empty($this->fileName)) {
            return $file->getClientOriginalName();
        }

        $fileType = explode('/', $file->getClientOriginalType())[1];

        return $this->fileName . '.' . $fileType;
    }

    /**
     * Undocumented function
     *
     * @param string $path
     * @param string $name
     * @param [type] $stream
     * @return void
     */
    public function writeStream(string $path, string $name, $stream)
    {
        $realPath = $this->destinationFile($path, $name);
        $outputStream = fopen($realPath, 'wb');

        // Read and write in chunks
        $chunkSize = 4096; // 4KB per chunk
        while (! feof($stream)) {
            $buffer = fread($stream, $chunkSize);
            fwrite($outputStream, $buffer);
        }

        // Close file streams
        fclose($stream);
        fclose($outputStream);
        return true;
    }

    /**
     * Path is already exist or not
     * If not exists this path create this path in file sytem
     * @param string $path
     * @return string
     */
    public function isDirectoryExists(string $path): string
    {
        $realPath = $this->storeageBasePath();

        if (! $this->isDirectory($realPath . '/' . trim($path, '/'))) {

            $this->makeDirectory($realPath . '/' . $path, 0777, true);
        }
        return $path;
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param  string  $directory
     * @return bool
     */
    public function isDirectory(string $directory): bool
    {
        return is_dir($directory);
    }

    /**
     * create the destination full path according in os
     *
     * @param string $path
     * @return string
     */
    public function destinationFile(string $path, $fileName): string
    {
        return $this->filePath . '/' . $path . '/' . $fileName;
    }

    /**
     * return project base path in os
     *
     * @return string
     */
    public function storeageBasePath(): string
    {
        return $this->filePath;
    }

    /**
     * Get the contents of a file.
     *
     * @param  string  $path
     * @param  bool  $lock
     * @return string
     *
     * @throws \Zuno\Support\Storage\FileNotFoundException
     */
    public function get($path, $lock = false)
    {
        if ($this->isFile($path)) {
            return file_get_contents($path);
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }

    /**
     * Determine if the given path is a file.
     *
     * @param  string  $file
     * @return bool
     */
    public function isFile($file)
    {
        return is_file($file);
    }

    /**
     * Create a directory.
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @param  bool  $force
     * @return bool
     */
    public function makeDirectory(string $path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }
}
