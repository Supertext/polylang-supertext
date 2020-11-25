<?php

namespace Supertext\Polylang\Proofreading;

class Proofreading{
  private static $instance = null;

  const METABOX_ID = 'proofreading-metabox';

  public function __construct(){
    self::$instance = $this;
  }

  public static function getInstance(){
    return self::$instance;
  }

  public function setupMetaBox(){
    add_meta_box(self::METABOX_ID, __('Proofreading', 'plugin-supertext'), array($this, 'addMetaBox'), null, 'side', 'high');
  }

  public function addMetaBox(){
    // TODO
    $btnHtml = '<a href="#" onclick="Supertext.Polylang.openOrderForm(\'en\', true)">' . __('Order proofread', 'plugin-supertext') . '</a>';
    echo $btnHtml;
  }
}

?>