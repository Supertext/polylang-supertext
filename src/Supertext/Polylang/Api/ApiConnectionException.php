<?php

namespace Supertext\Polylang\Api;


class ApiConnectionException extends \Exception
{
  public function __construct($message){
    parent::__construct(__('Supertext API call failed.', 'polylang-supertext').' '.$message);
  }
}