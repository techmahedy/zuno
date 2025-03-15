<?php

namespace Zuno\Support\Storage;

use Zuno\Support\Storage\LocalFileSystem;
use Zuno\Support\Storage\DiskNotFoundException;

class StorageFileService
{
    /**
     * The array of resolved filesystem drivers.
     *
     * @var array
     */
    protected $disks = [];

    /**
     * Get a filesystem instance.
     *
     * @param  string|null  $name
     * @return \Zuno\Support\Storage\IFileSystem
     */
    public function disk($name = null)
    {
        if ($name == 'local') {
            $path = $this->getDiskPath($name);
            return app(LocalFileSystem::class, [$path]);
        } else if ($name == 'public') {
            $path = $this->getDiskPath($name);
            return app(PublicFileSystem::class, [$path]);
        }

        throw new DiskNotFoundException("{$name} disk dose not support.");
    }

    /**
     * Return Storage Base Path
     *
     * @param string $disk
     * @return string
     */
    public function getDiskPath(string $disk): string
    {
        $path = config("filesystem.disks.{$disk}.root");

        return $path;
    }
}
