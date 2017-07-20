<?php

namespace Supertext\Polylang\Backend;


class PostCreationException extends \Exception
{
  public function __construct(){
    parent::__construct(__('Target post creation failed.', 'polylang-supertext'));
  }
}