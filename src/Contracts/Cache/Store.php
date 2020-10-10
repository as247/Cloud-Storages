<?php


namespace As247\CloudStorages\Contracts\Cache;


interface Store
{
	public function put($path, $data, $seconds=3600);
	public function forever($path, $value);
	public function get($path);
	public function has($path);

	public function forget($path,$bubble = false);
	public function forgetDir($path);

	public function delete($path, $bubble = false);
	public function deleteDir($path);

	public function move($source, $destination);

	public function flush();

	/**
	 * Query for matching path
	 * @param $path
	 * @param string|int $match * content in current directory ** include subdirectory
	 * @return mixed
	 */
	public function query($path, $match = '*');

	public function complete($path, $isCompleted = true);

	public function isCompleted($path);
}
