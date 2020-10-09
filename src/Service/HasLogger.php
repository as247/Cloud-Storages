<?php


namespace As247\CloudStorages\Service;


trait HasLogger
{
    protected $logger;
    public function getLogger(){
        return $this->logger;
    }
    public function setLogger($logger){
        $this->logger=$logger;
        return $this;
    }
    protected function setupLogger($logging){
    	$dir=__DIR__.'/../../';
    	$enable=false;
    	if($logging) {
			if (is_string($logging)) {
				$dir = $logging;
				$enable = true;
			} elseif (is_bool($logging)) {
				$enable = $logging;
			}elseif(is_array($logging)){
				$dir=$logging['dir']??'';
				$enable=$logging['enable']??false;
			}
		}
		$this->logger=new Logger($dir);
    	$this->logger->enable($enable);
	}
}
