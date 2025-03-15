<?php

namespace Zuno\Support\Storage;

use Zuno\Support\File;

class LocalFileSystem extends FileSystem implements IFileSystem
{
    /**
     * Store file as user defined path
     * @param string $path
     * @param File $uploadFile
     *
     * @return bool
     */
    public function store(string $path, File $uploadFile): bool
    {
        return $this->put($this->isDirectoryExists($path), $uploadFile);
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
}
