<?php


namespace As247\CloudStorages\Cache\Storage;

use As247\CloudStorages\Service\GoogleDrive;
use As247\CloudStorages\Support\Path;

class GoogleDriveStore extends ArrayStore
{
	protected $id;
	protected $dirMap=[];
	public function __construct($root=null)
	{

	}

	function map($dir,$file){
		$this->dirMap[Path::clean($dir)]=$file;
		return $this;
	}
	function has($key)
	{
		$key=Path::clean($key);
		if(isset($this->dirMap[$key])){
			return true;
		}
		return parent::has($key);
	}
	function get($key, $default = null)
	{
		$key=Path::clean($key);
		$dir=$this->dirMap[$key]??null;
		if($dir){
			if($dir instanceof \Google_Service_Drive_DriveFile){
				return $dir;
			}
			$dRoot = new \Google_Service_Drive_DriveFile();
			$dRoot->setId($dir);
			$dRoot->setMimeType(GoogleDrive::DIR_MIME);
			return $dRoot;
		}
		return parent::get($key, $default);
	}

}
