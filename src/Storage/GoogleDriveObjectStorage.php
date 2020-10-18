<?php


namespace As247\CloudStorages\Storage;


use As247\CloudStorages\Cache\Stores\GoogleDrivePersistentStore;
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
	public function __construct(GoogleDrive $drive)
	{
		$this->storage=$drive;
		$dataFile=__DIR__.'/'.$drive->getRoot().'.sqlite';
		$this->objCache=new SqliteCache($dataFile);
		$this->storageCache=$drive->getCache();
		if($this->storageCache->getStore() instanceof GoogleDrivePersistentStore){
			throw new ObjectStorageException('Persistent cache not work with object storage');
		}
	}

	public function readObject($urn)
	{
		$this->storageCache->mapFile($urn,$this->objCache->get($urn));
		try {
			return $this->storage->readStream($urn);
		} catch (FilesystemException $e) {
			$this->objCache->forget($urn);
			throw $e;
		}
	}

	public function writeObject($urn, $stream)
	{
		$this->storageCache->mapFile($urn,$this->objCache->get($urn));
		try {
			$this->storage->writeStream($urn, $stream);
		} catch (FilesystemException $e) {
			$this->objCache->forget($urn);
			throw $e;
		}
		try {
			$meta = $this->storage->getMetadata($urn);
			$this->objCache->put($urn,$meta['@id']);
		} catch (FileNotFoundException $e) {
			$this->objCache->forget($urn);
			throw $e;
		}
	}

	public function deleteObject($urn)
	{
		$this->storageCache->mapFile($urn,$this->objCache->get($urn));
		try {
			$this->storage->delete($urn);
		} catch (FileNotFoundException $e) {

		} finally {
			$this->objCache->forget($urn);
		}
	}

	public function objectExists($urn)
	{
		$this->storageCache->mapFile($urn,$this->objCache->get($urn));
		try {
			$this->storage->getMetadata($urn);
			return true;
		} catch (FileNotFoundException $e) {
			return false;
		}
	}
}
