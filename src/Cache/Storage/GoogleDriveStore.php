<?php


namespace As247\CloudStorages\Cache\Storage;

use As247\CloudStorages\Service\GoogleDrive;
use As247\CloudStorages\Support\Path;
use Google_Service_Drive_DriveFile;

class GoogleDriveStore extends ArrayStore
{
	function mapDirectory($path,$id){
		$file=$id;
		if(!$file instanceof Google_Service_Drive_DriveFile){
			$file = new Google_Service_Drive_DriveFile();
			$file->setId($id);
			$file->setMimeType(GoogleDrive::DIR_MIME);
		}
		$this->forever(Path::clean($path),$file);
		return $this;
	}

}
