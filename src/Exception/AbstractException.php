<?php

namespace As247\CloudStorages\Exception;

abstract class AbstractException extends \RuntimeException
{
    public $type;
    public function __construct($message = "", $type='', \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->type=$type;
    }
}