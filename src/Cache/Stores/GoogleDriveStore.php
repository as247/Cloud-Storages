<?php


namespace As247\CloudStorages\Cache\Stores;

use As247\CloudStorages\Service\GoogleDrive;
use As247\CloudStorages\Support\Path;
use Google_Service_Drive_DriveFile;

class GoogleDriveStore extends ArrayStore
{
	function mapDirectory($path,$id){
		return $this->mapFile($path,$id,GoogleDrive::DIR_MIME);
	}
	function mapFile($path,$id,$mimeType=''){
		if(!$id){
			return $this;
		}
		$file=$id;
		if(!$file instanceof Google_Service_Drive_DriveFile){
			$file = new Google_Service_Drive_DriveFile();
			$file->setId($id);
			$file->setMimeType($mimeType);
		}
		$this->forever(Path::clean($path),$file);
		return $this;
	}

}
