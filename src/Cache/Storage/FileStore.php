<?php


namespace As247\CloudStorages\Cache\Storage;


use As247\CloudStorages\Contracts\Cache\Store;

class FileStore implements Store
{
	protected $cacheDir;
	protected $filesDir;
	protected $completedDir;
	function __construct($namespace='')
	{
		$name=md5(static::class.serialize(func_get_args()));
		$this->cacheDir=sys_get_temp_dir().'/'.$name;
		$this->filesDir=$this->cacheDir.'/files';
		$this->completedDir=$this->cacheDir.'/completed';
		$this->ensureCacheDir();

	}
	protected function ensureCacheDir(){
		if(!is_dir($this->filesDir)){
			@$created=$this->mkdirp($this->filesDir);
			if(!$created){
				@unlink($this->filesDir);//It may be a file
				@$created=$this->mkdirp($this->filesDir);
			}
			if(!$created){
				throw new \RuntimeException('Could not create directory '.$this->filesDir);
			}
		}
		if(!is_dir($this->completedDir)){
			@$created=$this->mkdirp($this->completedDir);
			if(!$created){
				@unlink($this->completedDir);//It may be a file
				@$created=$this->mkdirp($this->completedDir);
			}
			if(!$created){
				throw new \RuntimeException('Could not create directory '.$this->completedDir);
			}
		}

	}
	function get($key){
		return $this->getPayload($key)['data'] ?? null;
	}
	function put($key,$value,$expires=3600){
		$path=$this->getCacheFile($key);
		$this->mkdirp(dirname($path));
		file_put_contents($path,
			serialize([
				'data' => $value,
				'expires' => $expires,
				'created'=>time(),
			]));
	}
	public function has($key)
	{
		return !empty($this->getPayload($key));
	}
	public function forget($key)
	{
		$path=$this->getCacheFile($key);
		if(is_dir($dir=$this->getCacheFile($key,''))){
			$this->rrmdir($dir);
		}
		@unlink($path);
	}

	public function forever($key, $value)
	{
		$this->put($key,$value,0);
	}

	public function flush()
	{
		$this->rrmdir($this->cacheDir);
	}

	function rename($source, $destination)
	{
		if(empty($destination)){
			$this->forget($source);
		}
		$cacheDir=$this->getCacheFile($source,'');
		$newCacheDir=$this->getCacheFile($destination,'');
		$cacheFile=$this->getCacheFile($source);
		$newCacheFile=$this->getCacheFile($destination);
		if($cacheFile){//Source is file
			$this->forget(dirname($destination));//Clear desination folder info
		}
		$this->renameCacheFile($cacheDir,$newCacheDir);
		$this->renameCacheFile($cacheFile,$newCacheFile);


	}
	protected function renameCacheFile($cacheFile,$newCacheFile){
		if (file_exists($cacheFile)) {
			$this->mkdirp(dirname($newCacheFile));
			if (file_exists($newCacheFile)) {
				unlink($newCacheFile);
			}
			rename($cacheFile, $newCacheFile);
		}
	}
	function completed($path)
	{
		return false;
	}
	function complete($path, $isCompleted = true, $expires=3600)
	{
		/*$path=$this->getCacheCompletedFile($path);
		$this->mkdirp(dirname($path));
		file_put_contents($path,
			serialize([
				'data' => $isCompleted,
				'expires' => $expires,
				'created'=>time(),
			]));
		*/
	}
	function query($path, $match = '*')
	{
		// TODO: Implement query() method.
	}

	/**
	 * Retrieve an item and expiry time from the cache by key.
	 *
	 * @param  $key
	 * @param $forCompleted
	 * @return array
	 */
	protected function getPayload($key,$forCompleted=false)
	{
		$path = $this->getCacheFile($key);
		if($forCompleted){
			$path=$this->getCacheCompletedFile($key);
		}
		$payload=[];
		if(file_exists($path) && is_file($path)){
			$content=file_get_contents($path);
			if($content) {
				$payload = unserialize($content);
				if(!isset($payload['data']) || !isset($payload['expires']) || !isset($payload['created'])){
					return [];
				}
				if($payload['expires']> 0 && $payload['created']+$payload['expires'] < time()){
					return [];
				}
			}
		}
		return $payload;

	}
	protected function getCacheFile($key,$ext='.cache'){
		return $this->filesDir.'/'.$this->sanitizeKey($key).$ext;
	}
	protected function getCacheCompletedFile($key,$ext='.cache'){
		return $this->completedDir.'/'.$this->sanitizeKey($key).$ext;
	}
	protected function sanitizeKey($key){
		$key= ltrim($key,'/');
		if(empty($key)){
			$key='__root__';
		}
		return $key;
	}
	function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
						$this->rrmdir($dir. DIRECTORY_SEPARATOR .$object);
					else
						unlink($dir. DIRECTORY_SEPARATOR .$object);
				}
			}
			rmdir($dir);
		}
	}
	protected function mkdirp($dir){
		if(is_dir($dir)){
			return true;
		}
		return mkdir($dir, 0750, true);
	}

}
