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
   * @param string $name The name of the view
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

  /**
   * @param $nodes
   * @return string
   */
  protected function convertToHtmlListTree($nodes)
  {
    $nodeHtml = '<ul>';

    foreach ($nodes as $node) {
      $id = $node['id'];
      $icon = $node['type'] === 'field' ? 'jstree-file' : 'jstree-folder';

      $nodeHtml .= '<li id="' . $id . '" data-jstree=\'{"icon":"' . $icon . '"}\'>';
      $nodeHtml .= $node['label'];

      if (!empty($node['sub_field_definitions'])) {
        $nodeHtml .= $this->convertToHtmlListTree($node['sub_field_definitions']);
      }

      $nodeHtml .= '</li>';
    }

    $nodeHtml .= '</ul>';

    return $nodeHtml;
  }
}