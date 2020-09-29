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
}
