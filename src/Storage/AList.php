<?php

namespace As247\CloudStorages\Storage;

use As247\CloudStorages\Exception\FileNotFoundException;
use As247\CloudStorages\Exception\UnableToCreateDirectory;
use As247\CloudStorages\Exception\UnableToWriteFile;
use As247\CloudStorages\Service\AListService;
use As247\CloudStorages\Support\Config;
use As247\CloudStorages\Support\FileAttributes;
use As247\CloudStorages\Support\Path;
use GuzzleHttp\Exception\ClientException;
use Traversable;

class AList extends Storage
{
    protected $service;
    protected $root = '';
    public function __construct($url, $options = [])
    {
        $this->service = new AListService($url, $options);
        $this->root=Path::clean($options['root']??'');
    }

    public function getService()
    {
        return $this->service;
    }

    public function writeStream(string $path, $contents, Config $config = null): void
    {
        try {
            $this->service->put($path, $contents);
        } catch (ClientException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage());
        }
    }

    public function readStream(string $path)
    {
        try {
            $contents = $this->service->read($path);
            if ($contents) {
                return $contents;
            }
            throw new FileNotFoundException($path);
        } catch (ClientException $e) {
            throw new FileNotFoundException($path);
        }
    }

    public function delete(string $path): void
    {
        if(Path::clean($path)===$this->root){
            return ;
        }
        $this->service->remove(dirname($path),basename($path));
    }

    public function deleteDirectory(string $path): void
    {
        $this->delete($path);
    }

    public function createDirectory(string $path, Config $config = null): void
    {
        try {
            $created=$this->service->mkdir($path);
            if(!$created){
                throw UnableToCreateDirectory::atLocation($path);
            }
        }catch (ClientException $e){
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage());
        }
    }

    public function setVisibility(string $path, $visibility): void
    {
        // TODO: Implement setVisibility() method.
    }

    public function listContents(string $path, bool $deep): Traversable
    {
        try {
            $results = $this->service->listContents($path);
            foreach ($results as $id => $result) {
                $result = $this->service->normalizeMetadata($result, rtrim($path,'\/') . '/' . $result['name']);
                yield $id => $result;
                if ($deep && $result['type'] === 'dir') {
                    yield from $this->listContents($result['path'], $deep);
                }
            }
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                yield from [];
            }
        }
    }

    public function move(string $source, string $destination, Config $config = null): void
    {
        $this->copy($source,$destination,$config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config = null): void
    {
        $srcDir=dirname($source);
        $dstDir=dirname($destination);
        $tmpDir=Path::join($srcDir,'.tmp');
        $this->createDirectory($tmpDir);
        $this->createDirectory($dstDir);
        $this->service->copy($srcDir,$tmpDir,basename($source));
        $this->service->rename(Path::join($tmpDir,basename($source)),basename($destination));
        $this->service->move($tmpDir,$dstDir,basename($destination));

    }

    /**
     * @param $path
     * @return FileAttributes
     */
    public function getMetadata($path): FileAttributes
    {
        $meta=$this->service->get($path);
        if(!$meta){
            throw new FileNotFoundException($path);
        }
        $attributes=$this->service->normalizeMetadata($meta,$path);
        return new FileAttributes($attributes);
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, Config $config = null): string
    {
        $expire=$expiresAt->getTimestamp();
        return $this->service->getDownloadUrl($path,$expire);
    }
}