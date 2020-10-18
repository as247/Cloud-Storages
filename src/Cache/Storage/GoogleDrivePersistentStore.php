<?php


namespace As247\CloudStorages\Cache\Storage;


use As247\CloudStorages\Cache\SqliteCache;

class GoogleDrivePersistentStore extends GoogleDriveStore
{
	protected $sqliteCache;
	public function __construct($cacheFile)
	{
		$this->sqliteCache=new SqliteCache($cacheFile);
	}
}
