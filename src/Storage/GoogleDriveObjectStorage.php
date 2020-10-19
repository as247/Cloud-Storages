<?php


namespace As247\CloudStorages\Storage;


use As247\CloudStorages\Cache\Stores\GoogleDrivePersistentStore;
use As247\CloudStorages\Cache\Stores\NullStore;
use As247\CloudStorages\Cache\Stores\SqliteCache;
use As247\CloudStorages\Contracts\Storage\ObjectStorage;
use As247\CloudStorages\Exception\FileNotFoundException;
use As247\CloudStorages\Exception\FilesystemException;
use As247\CloudStorages\Exception\ObjectStorageException;

class GoogleDriveObjectStorage implements ObjectStorage
{
	protected $storage;
	protected $objCache;
	protected $storageCache;
	public function __construct(GoogleDrive $drive,$cacheFilePath=null)
	{
		$this->storage=$drive;
		if($cacheFilePath===null) {
			$cacheFilePath = sys_get_temp_dir() . '/' . $drive->getRoot() . '.sqlite';
		}
		if($cacheFilePath) {
			$this->objCache = new SqliteCache($cacheFilePath);
		}else{
			$this->objCache = new NullStore();
		}
		$this->storageCache=$drive->getCache();
		if($this->storageCache->getStore() instanceof GoogleDrivePersistentStore){
			throw new ObjectStorageException('Persistent cache not work with object storage');
		}
	}

	public function readObject($urn)
	{
		$urn=$this->sanitizeUrn($urn);
		$this->updateCache($urn);
		return $this->storage->readStream($urn);
	}

	public function writeObject($urn, $stream)
	{
		$urn=$this->sanitizeUrn($urn);
		$this->storageCache->mapFile($urn,$this->objCache->get($urn));
		print_r($this->storageCache->query('/'));
		try {
			$this->storage->writeStream($urn, $stream);
		} catch (FilesystemException $e) {
			$this->objCache->forget($urn);
			throw $e;
		}
		//File write success update cache
		$this->updateCache($urn);
	}

	public function deleteObject($urn)
	{
		$urn=$this->sanitizeUrn($urn);
		$this->storageCache->mapFile($urn,$this->objCache->get($urn));
		try {
			$this->storage->delete($urn);
		} catch (FileNotFoundException $e) {
			//Already deleted do nothing
		} finally {
			$this->objCache->forget($urn);
		}
	}

	public function objectExists($urn)
	{
		$urn=$this->sanitizeUrn($urn);
		try {
			$this->updateCache($urn);
			return true;
		} catch (FileNotFoundException $e) {
			return false;
		}
	}
	protected function updateCache($urn){
		$cached=$this->objCache->get($urn);
		if($cached) {
			$this->storageCache->mapFile($urn, $cached);
		}
		try {
			$meta=$this->storage->getMetadata($urn);
			$this->objCache->put($urn,$meta['@id']);
			return true;
		} catch (FileNotFoundException $e) {
			$this->objCache->forget($urn);
			throw $e;
		}
	}

	protected function sanitizeUrn($urn){
		return str_replace(['/','\\'],':',$urn);
	}
}
