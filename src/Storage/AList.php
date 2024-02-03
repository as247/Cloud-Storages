<?php

namespace As247\CloudStorages\Storage;

use As247\CloudStorages\Exception\FileNotFoundException;
use As247\CloudStorages\Exception\StorageException;
use As247\CloudStorages\Service\AListService;
use As247\CloudStorages\Support\Config;
use As247\CloudStorages\Support\FileAttributes;
use As247\CloudStorages\Support\Path;
use Traversable;

class AList extends Storage
{
    protected $service;
    protected $root = '';
    protected $fakeVisibility=[];
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
        $this->service->put($path, $contents);
        if($config){
            $this->setVisibility($path,$config->get('visibility'));
        }
    }

    public function readStream(string $path)
    {
        $contents = $this->service->read($path);
        if ($contents) {
            return $contents;
        }
        throw new StorageException('Unable to read file: ' . $path,'readStream');
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
        $created=$this->service->mkdir($path);
        if(!$created){
            throw new StorageException('Unable to create directory: ' . $path,'createDirectory');
        }
    }

    public function setVisibility(string $path, $visibility): void
    {
        $this->getMetadata($path);
        $this->fakeVisibility[$path]=$visibility;
        //throw new StorageException('setVisibility is not supported by AList','setVisibility');
    }

    public function listContents(string $path, bool $deep): Traversable
    {
        $results = $this->service->listContents($path);
        foreach ($results as $id => $result) {
            $result = $this->service->normalizeMetadata($result, rtrim($path,'\/') . '/' . $result['name']);
            yield $id => $result;
            if ($deep && $result['type'] === 'dir') {
                yield from $this->listContents($result['path'], $deep);
            }
        }
    }

    public function move(string $source, string $destination, Config $config = null): void
    {
        if(Path::clean($source)===Path::clean($destination)){
            throw new StorageException('Source and destination are the same: ' . $source,'move');
        }
        $this->getMetadata($source);
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
        if($config){
            $this->setVisibility($destination,$this->getMetadata($source)->visibility());
        }

    }

    /**
     * @param $path
     * @return FileAttributes
     */
    public function getMetadata($path): FileAttributes
    {
        $meta=$this->service->get($path);
        if(!$meta){
            throw FileNotFoundException::create($path);
        }
        $attributes=$this->service->normalizeMetadata($meta,$path);
        if(isset($this->fakeVisibility[$path])){
            $attributes['visibility']=$this->fakeVisibility[$path];
        }
        return new FileAttributes($attributes);
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, Config $config = null): string
    {
        $expire=$expiresAt->getTimestamp();
        return $this->service->getDownloadUrl($path,$expire);
    }
}