<?php

namespace Supertext\Api;


class ApiDataException extends \Exception
{
  public function __construct($message){
    parent::__construct(__('Supertext API returned unsupported data.', 'supertext').' '.$message);
  }
}