<?php


namespace As247\CloudStorages\Contracts\Cache;


interface Store
{
	/**
	 * Put value to cache, update if existing
	 * @param $path
	 * @param $data
	 * @param int $seconds
	 * @return mixed
	 */
	public function put($path, $data, $seconds=3600);

	/**
	 * Never expire cache
	 * @param $path
	 * @param $value
	 * @return mixed
	 */
	public function forever($path, $value);

	/**
	 * Get or return default
	 * @param $path
	 * @param null $default
	 * @return mixed
	 */
	public function get($path, $default=null);

	/**
	 * Check if given path exists in cache
	 * @param $path
	 * @return mixed
	 */
	public function has($path);

	/**
	 * For get a path and all parents if bubble is true
	 * @param $path
	 * @param false $bubble
	 * @return mixed
	 */
	public function forget($path,$bubble = false);

	/**
	 * Forget a path and all its children
	 * eg if forget /a then /a/b /a/b/c also removed
	 * @param $path
	 * @return mixed
	 */
	public function forgetDir($path);

	/**
	 * Set false value for $path and its parent if $bubble
	 * @param $path
	 * @param false $bubble
	 * @return mixed
	 */
	public function delete($path, $bubble = false);

	/**
	 * Set false value for $path and all existing children
	 * Eg: If deleteDir('/a') is called and '/a/b','/a/c/e.txt' exists in cache
	 * 			Then all of them  set to false
	 * @param $path
	 * @return mixed
	 */
	public function deleteDir($path);

	/**
	 * Simulate rename function, we need to move all value from $source tree to $destination
	 *
	 * @param $source
	 * @param $destination
	 * @return mixed
	 */
	public function move($source, $destination);

	/**
	 * Flush cache
	 * @return mixed
	 */
	public function flush();

	/**
	 * Query for matching path
	 * @param $path
	 * @param string|int $match * content in current directory ** include subdirectory
	 * @return mixed
	 */
	public function query($path, $match = '*');

	/**
	 * Mark the path is completed that mean nothing under this path is outside cache, used for listing
	 * @param $path
	 * @param bool $isCompleted
	 * @return mixed
	 */
	public function complete($path, $isCompleted = true);

	/**
	 * Check if current path is completed
	 * @param $path
	 * @return mixed
	 */
	public function isCompleted($path);
}
