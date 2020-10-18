<?php


namespace As247\CloudStorages\Cache\Storage;


use As247\CloudStorages\Cache\SqliteCache;
use As247\CloudStorages\Support\Path;

class GoogleDrivePersistentStore extends GoogleDriveStore
{
	protected $sqliteCache;
	public function __construct($cacheFile)
	{
		$this->sqliteCache=new SqliteCache($cacheFile);
	}
	public function put($key, $data, $seconds = 3600)
	{
		$key=Path::clean($key);
		if(!$data){
			parent::put($key,$data);//Keep false item in object cache
			$this->sqliteCache->forget($key);
		}else{
			parent::put($key,null);
			$this->sqliteCache->forever($key,$data);
		}
	}
	public function get($key)
	{
		if(null!==($parentValue=parent::get($key))){
			return $parentValue;
		}
		$key=Path::clean($key);
		$cache= $this->sqliteCache->get($key);
		return $cache;
	}

	function query($path, $deep = 1)
	{
		$path=Path::clean($path);
		$list=$this->sqliteCache->keyStartedWith($path);
		if($deep===0){
			return $list;
		}
		unset($list[$path]);
		return $list;

	}
	public function complete($path, $isCompleted = true)
	{

	}
}
