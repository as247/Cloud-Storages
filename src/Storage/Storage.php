<?php


namespace As247\CloudStorages\Storage;


use As247\CloudStorages\Cache\NullCache;
use As247\CloudStorages\Cache\PathObjectCache;
use As247\CloudStorages\Contracts\Cache\CacheInterface;
use As247\CloudStorages\Contracts\Cache\PathCacheInterface;
use As247\CloudStorages\Contracts\Storage\StorageContract;
use As247\CloudStorages\Service\HasLogger;

abstract class Storage implements StorageContract
{
	/**
	 * @var PathCacheInterface
	 */
	protected $cache;
	use HasLogger;
	protected function setupCache($options){
		if(!isset($options['cache'])){
			$options['cache']=new PathObjectCache();
		}
		if($options['cache'] instanceof \Closure){
			$options['cache']=$options['cache']();
		}
		if($options['cache']===false || $options['cache']==='null'){
			$options['cache']=new NullCache();
		}
		if(!$options['cache'] instanceof PathCacheInterface){
			$options['cache']=new PathObjectCache();
		}
		$this->setCache($options['cache']);
	}
	public function setCache(PathCacheInterface $cache){
		$this->cache=$cache;
		return $this;
	}
	public function getCache(){
		return $this->cache;
	}
}
