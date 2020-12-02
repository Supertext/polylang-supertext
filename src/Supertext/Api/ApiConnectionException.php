<?php

namespace Supertext\Api;


class ApiConnectionException extends \Exception
{
  public function __construct($message){
    parent::__construct(__('Supertext API connection failed.', 'supertext').' '.$message);
  }
}