<?php


namespace As247\CloudStorages\Contracts\Cache;


interface Store
{
	public function put($key, $data, $seconds=3600);

	public function get($key);

	public function has($key);

	public function forget($key);

	public function forever($key, $value);

	public function flush();

	public function rename($source, $destination);

	/**
	 * Query for matching path
	 * @param $path
	 * @param string|int $match * content in current directory ** include subdirectory
	 * @return mixed
	 */
	public function query($path, $match = '*');

	public function complete($path, $isCompleted = true);

	public function completed($path);
}
