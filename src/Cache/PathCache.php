<?php


namespace As247\CloudStorages\Cache;


use As247\CloudStorages\Cache\Storage\ArrayStore;
use As247\CloudStorages\Support\Path;

class PathCache
{
	protected $storage;
	public function __construct()
	{
		$this->storage = new ArrayStore();
	}

	public function put($key, $data, $seconds = 3600)
	{
		$key=Path::clean($key);
		$this->storage->put($key,$data,$seconds);
	}

	public function get($key)
	{
		$key=Path::clean($key);
		return $this->storage->get($key);
	}

	public function has($key)
	{
		$key=Path::clean($key);
		return $this->storage->has($key);
	}

	public function forget($key)
	{
		$key=Path::clean($key);
		$this->storage->forget($key);
	}

	public function forever($key, $value)
	{
		$key=Path::clean($key);
		$this->storage->forever($key,$value);
	}

	public function flush()
	{
		$this->storage->flush();
	}

	public function rename($source, $destination)
	{
		$source=Path::clean($source);
		if(is_string($destination)) {
			$destination = Path::clean($destination);
		}
		$this->storage->rename($source,$destination);
	}

	public function query($path, $match = '*')
	{
		$path=Path::clean($path);
		return $this->storage->query($path,$match);
	}

	public function complete($path, $isCompleted = true)
	{
		$path=Path::clean($path);
		$this->storage->complete($path,$isCompleted);
	}

	public function completed($path)
	{
		$path=Path::clean($path);
		return $this->storage->completed($path);
	}
}
