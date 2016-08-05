<?php

namespace Supertext\Polylang\Api;


class ApiConnectionException extends \Exception
{
  public function __construct($message){
    parent::__construct(__('Supertext API connection failed.', 'polylang-supertext').' '.$message);
  }
}