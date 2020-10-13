<?php


namespace As247\CloudStorages\Cache;


use As247\CloudStorages\Cache\Storage\ArrayStore;
use As247\CloudStorages\Cache\Storage\GoogleDriveStore;
use As247\CloudStorages\Contracts\Cache\Store;


/**
 * Class PathCache
 * @package As247\CloudStorages\Cache
 * @mixin Store
 */
class PathCache
{
	protected $store;
	public function __construct(Store $store=null)
	{
		if($store==null) {
			$store=new ArrayStore();
		}
		$this->store = $store;
	}

	/**
	 * @return ArrayStore|GoogleDriveStore|Store|null
	 */
	public function getStore(){
		return $this->store;
	}

	public function __call($name, $arguments)
	{
		return $this->store->$name(...$arguments);
	}
}
