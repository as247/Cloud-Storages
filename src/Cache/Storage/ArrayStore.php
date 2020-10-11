<?php


namespace As247\CloudStorages\Cache\Storage;

use As247\CloudStorages\Contracts\Cache\Store;
use As247\CloudStorages\Support\Path;

class ArrayStore implements Store
{
	protected $files = [];
	protected $completed = [];

	public function put($key, $data, $seconds = 3600)
	{
		$key = Path::clean($key);
		$this->files[$key] = $data;
	}

	public function forever($key, $value)
	{
		$key = Path::clean($key);
		$this->files[$key] = $value;
	}

	public function get($key, $default=null)
	{
		$key = Path::clean($key);
		return $this->files[$key] ?? $default;
	}

	public function has($key)
	{
		$key = Path::clean($key);
		return array_key_exists($key, $this->files);
	}

	public function forget($path, $bubble = false)
	{
		$path=Path::clean($path);
		if ($bubble) {
			$tmpPath = $path;
			do  {
				unset($this->files[$tmpPath]);
				unset($this->completed[$tmpPath]);
			}while(($tmpPath = Path::clean(dirname($tmpPath))) && $tmpPath !== '/');
		} else {
			unset($this->files[$path]);
			unset($this->completed[$path]);
		}
	}

	public function delete($path, $bubble = false)
	{
		$path=Path::clean($path);
		if ($bubble) {
			$tmpPath = $path;
			do{
				$this->files[$tmpPath] = false;
			}
			while (($tmpPath = Path::clean(dirname($tmpPath))) && $tmpPath !== '/');
		} else {
			$this->files[$path] = false;
		}
	}

	public function forgetDir($path)
	{
		$path=Path::clean($path);
		foreach ($this->files as $key => $file) {
			if (strpos($key, $path) === 0) {
				unset($this->files[$key]);
			}
		}
		foreach ($this->completed as $key => $value) {
			if (strpos($key, $path) === 0) {
				unset($this->completed[$key]);
			}
		}
	}

	public function deleteDir($path)
	{
		$path=Path::clean($path);
		foreach ($this->files as $key => $file) {
			if (strpos($key, $path) === 0) {
				$this->files[$key] = false;
			}
		}
		foreach ($this->completed as $key => $value) {
			if (strpos($key, $path) === 0) {
				unset($this->completed[$key]);
			}
		}
	}

	public function flush()
	{
		$root = $this->get('/');
		$this->files = [];
		$this->put('/', $root);
		$this->completed = [];
	}

	function move($from,$to)
	{
		if(!$from || !$to){
			throw new \RuntimeException("Invalid path");
		}
		$from=Path::clean($from);
		$to=Path::clean($to);
		//Destination tree changed we should clean up all parent
		$this->forget($to,true);
		foreach ($this->files as $key => $file) {
			$newKey = Path::replace($from, $to, $key);
			if ($newKey !== $key) {
				$this->files[$newKey] = $file;
				$this->files[$key] = false;
			}
		}
		foreach ($this->completed as $key => $value) {
			$newKey = Path::replace($from, $to, $key);
			if ($newKey !== $key) {
				$this->completed[$newKey] = $value;
				unset($this->completed[$key]);
			}
		}
	}

	public function query($path, $match = '*')
	{
		$directory = Path::clean($path);
		$results = [];
		$dirSegCount = Path::countSegments($directory);
		$deep = 0;
		if ($match === '*') {
			$deep = 1;
		}
		if (is_int($match)) {
			$deep = $match;
		}
		foreach ($this->files as $path => $file) {
			if (!$file) {
				continue;
			}
			if (strpos($path, $directory) === 0 && $path !== $directory) {
				if ($deep) {
					if (Path::countSegments($path) - $dirSegCount <= $deep) {
						$results[$path] = $file;
					}
				}
			}
		}
		return $results;
	}

	public function complete($path, $isCompleted = true)
	{
		$path = Path::clean($path);
		$this->completed[$path] = $isCompleted;
	}

	public function isCompleted($path)
	{
		$path = Path::clean($path);
		return $this->completed[$path] ?? false;
	}
}
