<?php


namespace As247\CloudStorages\Storage;

use As247\CloudStorages\Cache\PathCache;
use As247\CloudStorages\Contracts\Storage\StorageContract;
use As247\CloudStorages\Service\HasLogger;
use Closure;

abstract class Storage implements StorageContract
{
	/**
	 * @var PathCache
	 */
	protected $cache;
	use HasLogger;
	protected function setupCache($options){
		if(!isset($options['cache'])){
			$options['cache']=new PathCache();
		}
		if($options['cache'] instanceof Closure){
			$options['cache']=$options['cache']();
		}
		if(!$options['cache'] instanceof PathCache){
			$options['cache']=new PathCache();
		}
		$this->setCache($options['cache']);
	}
	public function setCache(PathCache $cache){
		$this->cache=$cache;
		return $this;
	}
	public function getCache(){
		return $this->cache;
	}
}
