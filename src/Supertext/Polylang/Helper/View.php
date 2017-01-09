<?php

namespace Supertext\Polylang\Helper;


class View
{
  /**
   * Name of the view to render
   */
  private $name = null;

  /**
   * Initialize a new view
   * @param $name The name of the view
   */
  public function __construct($name) {
    $this->name = $name;
  }

  /**
   * Render the view
   * @param array $context
   */
  public function render(Array $context = array()) {
    extract($context);

    include( SUPERTEXT_POLYLANG_VIEW_PATH . $this->name . '.php');
  }
}