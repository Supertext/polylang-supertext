<?php

namespace Supertext\Proofreading;

use Supertext\Helper\PostMeta;
use Supertext\Helper\ProofreadMeta;

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
    add_meta_box(self::METABOX_ID, __('Proofreading', 'supertext'), array($this, 'addMetaBox'), null, 'side', 'high');
  }

  public function addMetaBox(){
    $curLang = substr(get_locale(), 0, 2);
    $btnHtml = '<a href="#" onclick="Supertext.Interface.openOrderForm(\'' . $curLang . '\', true)">' . __('Order proofread', 'supertext') . '</a>';

    global $post;
    $meta = ProofreadMeta::of($post->ID);
    if($meta->is(ProofreadMeta::IN_PROOFREADING)){
      echo __('Post is still in the proofread process', 'supertext');
    }else {
      echo $btnHtml;
    }
  }
}

?>