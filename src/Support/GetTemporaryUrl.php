<?php

namespace As247\CloudStorages\Support;

trait GetTemporaryUrl
{
    public function getTemporaryUrl($path, $expiration=null, $options=[])
    {
        if($expiration===null){
            $expiration=time()+3600;
        }
        if(is_int($expiration)){
            $expiration=(new \DateTime())->setTimestamp($expiration);
        }
        return $this->storage->temporaryUrl($this->applyPathPrefix($path),$expiration,$this->convertConfig($options));
    }
}