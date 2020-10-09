<?php


namespace As247\CloudStorages\Cache;

use As247\CloudStorages\Support\Path;

class NullCache extends PathObjectCache
{

	public function put($key, $data, $seconds=0)
	{
		$key=Path::clean($key);
		if($key==='/'){
			parent::put($key,$data,$seconds);
		}
	}
	public function rename($from, $to)
	{

	}
	public function complete($path, $isCompleted = true)
	{

	}

}
