<?php


namespace As247\CloudStorages\Contracts\Cache;


interface CacheInterface
{
	public function put($key, $data, $seconds=3600);

	public function get($key);

	public function has($key);

	public function forget($key);

	public function forever($key, $value);

	public function flush();
}
