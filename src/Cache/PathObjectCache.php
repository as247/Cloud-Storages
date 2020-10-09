<?php


namespace As247\CloudStorages\Cache;


use As247\CloudStorages\Contracts\Cache\PathCacheInterface;
use As247\CloudStorages\Support\Path;


class PathObjectCache implements PathCacheInterface
{
	protected $files=[];
	protected $completed=[];
	protected $length=0;
	public function __construct()
	{

	}


	public function put($key, $data, $seconds=3600)
	{
		$key=Path::clean($key);
		$this->files[$key]=$data;
	}

	public function get($key)
	{
		$key=Path::clean($key);
		return $this->files[$key]??null;
	}

	public function has($key)
	{
		$key=Path::clean($key);
		return array_key_exists($key,$this->files);
	}

	public function forget($key)
	{
		$key=Path::clean($key);
		$this->rename($key,null);
	}

	public function forever($key, $value)
	{
		$key=Path::clean($key);
		$this->files[$key]=$value;
	}

	public function flush()
	{
		$this->length=0;
		$root=$this->get('/');
		$this->files=[];
		$this->put('/',$root);
		$this->completed=[];
	}

	public function rename($from, $to)
	{
		$from=Path::clean($from);
		$deleted=empty($to);
		$forget=$to===null;
		$to=Path::clean($to);
		foreach ($this->files as $key=>$file){
			if($deleted) {
				if(strpos($key,$from)===0){
					if($forget){
						unset($this->files[$key]);
					}else {
						$this->files[$key] = false;
					}
				}
			}else{
				$newKey = Path::replace($from, $to, $key);
				if ($newKey !== $key) {
					$this->files[$newKey] = $file;
					$this->files[$key] = false;
				}
			}
		}
		foreach ($this->completed as $key=>$value){
			if($deleted){
				if(strpos($key,$from)===0){
					unset($this->completed[$key]);
				}
			}else {
				$newKey = Path::replace($from, $to, $key);
				if ($newKey !== $key) {
					$this->completed[$newKey] = $value;
					unset($this->completed[$key]);
				}
			}
		}
	}

	public function query( $path, $match = '*')
	{
		$directory=Path::clean($path);
		$results=[];
		$dirSegCount=Path::countSegments($directory);
		$deep=0;
		if($match==='*'){
			$deep=1;
		}
		if(is_int($match)){
			$deep=$match;
		}
		foreach ($this->files as $path => $file) {
			if(!$file){
				continue;
			}
			if (strpos($path, $directory) === 0 && $path!==$directory) {
				if($deep) {
					if (Path::countSegments($path) - $dirSegCount <= $deep) {
						$results[$path] = $file;
					}
				}
			}
		}
		return $results;
	}

	public function complete( $path,  $isCompleted=true)
	{
		$path=Path::clean($path);
		$this->completed[$path]=$isCompleted;
	}

	public function completed( $path)
	{
		$path=Path::clean($path);
		return $this->completed[$path]??false;
	}
}
