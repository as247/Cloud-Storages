<?php


namespace As247\CloudStorages\Cache;


use As247\CloudStorages\Cache\Storage\ArrayStore;
use As247\CloudStorages\Contracts\Cache\Store;


/**
 * Class PathCache
 * @package As247\CloudStorages\Cache
 * @mixin Store
 */
class PathCache
{
	protected $storage;
	public function __construct()
	{
		$this->storage = new ArrayStore();
	}

	public function __call($name, $arguments)
	{
		return $this->storage->$name(...$arguments);
	}
}
