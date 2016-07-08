<?php

namespace Supertext\Polylang\Helper;

/**
 * Class TextProcessor
 * @package Supertext\Polylang\Helper
 */
class TextProcessor
{
  const SHORTCODE_TAG = 'div';
  const SHORTCODE_TAG_CLASS = 'polylang-supertext-shortcode';
  const SHORTCODE_CLOSED_TAG_CLASS = 'polylang-supertext-shortcode-closed';
  const SHORTCODE_ENCLOSED_CONTENT_CLASS = 'polylang-supertext-shortcode-enclosed';

  /**
   * @var Library
   */
  private $library;

  /**
   * @param Library $library
   */
  public function __construct($library)
  {
    $this->library = $library;
  }

  /**
   * Replaces the shortcodes with html nodes
   * @param string post content to process
   * @return string post content with replaced shortcodes
   */
  public function replaceShortcodes($content)
  {
    $options = $this->library->getSettingOption();
    $savedShortcodes = isset($options[Constant::SETTING_SHORTCODES]) ? $options[Constant::SETTING_SHORTCODES] : array();
    $regex = $this->library->getShortcodeRegex();

    return preg_replace_callback("/$regex/s", function ($m) use ($savedShortcodes) {
      return $this->replaceShortcode($m, $savedShortcodes);
    }, $content);
  }

  /**
   * Effectively replaces one shortcode with a html node.
   * @param $matches matches (0: match, 1, 6: escaping chars, 2: shortcode tag name, 3: attributes, 5: enclosed content/data)
   * @param $savedShortcodes saved shortcodes
   * @return string replacement string
   */
  private function replaceShortcode($matches, $savedShortcodes)
  {
    //return escaped shortcodes, do not replace
    if ($matches[1] == '[' && $matches[6] == ']') {
      return $matches[0];
    }

    $tagName = $matches[2];
    $attributes = shortcode_parse_atts($matches[3]);
    $savedShortcode = isset($savedShortcodes[$tagName]) ? $savedShortcodes[$tagName] : array('attributes' => array());
    $translatableShortcodeAttributes = $savedShortcode['attributes'];
    $forceEnclosingForm = preg_match('/\[\s*\/\s*' . $tagName . '\s*\]/', $matches[0]);

    $attributeNodes = $this->getAttributeNodes($attributes, $translatableShortcodeAttributes);

    //Enclosed content can contain shortcodes as well
    if (!empty($matches[5])) {
      $content = $matches[5];
      if (!empty($savedShortcode['content_encoding'])) {
        $content = $this->decodeEnclosedContent($content, $savedShortcode['content_encoding']);
      }
      $enclosedContent = $this->replaceShortcodes($content);
      $attributeNodes .= '<div class="' . self::SHORTCODE_ENCLOSED_CONTENT_CLASS . '">' . $enclosedContent . '</div>';
    }

    return '<' . self::SHORTCODE_TAG . ' class="' . ($forceEnclosingForm ? self::SHORTCODE_CLOSED_TAG_CLASS : self::SHORTCODE_TAG_CLASS) . '" name="' . $tagName . '" >' . $attributeNodes . '</' . self::SHORTCODE_TAG . '>';
  }

  /**
   * Replaces the shortcode html nodes with wordpress shortcodes
   * @param string post content to process
   * @return string post content with shortcodes
   */
  public function replaceShortcodeNodes($content)
  {
    $options = $this->library->getSettingOption();
    $savedShortcodes = isset($options[Constant::SETTING_SHORTCODES]) ? $options[Constant::SETTING_SHORTCODES] : array();

    $doc = $this->createHtmlDocument($content);

    $childNodes = $doc->getElementsByTagName('body')->item(0)->childNodes;

    return $this->replaceShortcodeNodesRecursive($doc, $childNodes, $savedShortcodes);
  }

  /**
   * @param $content
   * @return \DOMDocument
   */
  private function createHtmlDocument($content)
  {
    $html = '<?xml version="1.0" encoding="utf-8"?>
    <html>
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        </head>
        <body>' . $content . '</body>
    </html>
    ';

    $doc = new \DOMDocument();
    $doc->loadHTML($html);
    return $doc;
  }

  /**
   * Parses the child nodes and returns the new content with replaced shortcode nodes
   * @param \DOMDocument $doc the dom document
   * @param \DOMNodeList $childNodes the child nodes to process
   * @param $savedShortcodes
   * @return string the new content
   */
  private function replaceShortcodeNodesRecursive($doc, $childNodes, $savedShortcodes)
  {
    $newContent = '';

    foreach ($childNodes as $childNode) {

      if ($childNode->nodeType === XML_ELEMENT_NODE
        && $childNode->nodeName === self::SHORTCODE_TAG
        && $childNode->hasAttribute('class')
        && ($childNode->attributes->getNamedItem('class')->nodeValue === self::SHORTCODE_TAG_CLASS
          || $childNode->attributes->getNamedItem('class')->nodeValue === self::SHORTCODE_CLOSED_TAG_CLASS)
      ) {

        $shortcodeName = $childNode->attributes->getNamedItem('name')->nodeValue;
        $attributes = '';
        $enclosedContent = '';
        $forceEnclosingForm = $childNode->attributes->getNamedItem('class')->nodeValue === self::SHORTCODE_CLOSED_TAG_CLASS;

        foreach ($childNode->childNodes as $shortcodeChildNode) {
          switch ($shortcodeChildNode->nodeName) {
            case 'span':
              $attributeName = $shortcodeChildNode->attributes->getNamedItem('name')->nodeValue;
              if (isset($savedShortcodes[$shortcodeName])) {
                $attributeValue = $this->getAttributeValue($attributeName, $shortcodeChildNode->nodeValue, $savedShortcodes[$shortcodeName]['attributes']);
              } else {
                $attributeValue = $shortcodeChildNode->nodeValue;
              }
              $attributes .= $attributeName . '="' . $attributeValue . '" ';
              break;
            case 'input':
              $attributeName = $shortcodeChildNode->attributes->getNamedItem('name')->nodeValue;
              $attributeValue = $shortcodeChildNode->attributes->getNamedItem('value')->nodeValue;
              $attributes .= $attributeName . '="' . $attributeValue . '" ';
              break;
            case 'div':
              $enclosedContent = $this->replaceShortcodeNodesRecursive($doc, $shortcodeChildNode->childNodes, $savedShortcodes);

              if (isset($savedShortcodes[$shortcodeName]) && !empty($savedShortcodes[$shortcodeName]['content_encoding'])) {
                $enclosedContent = $this->encodeEnclosedContent($enclosedContent, $savedShortcodes[$shortcodeName]['content_encoding']);
              }

              break;
          }
        }

        $space = empty($attributes) ? '' : ' ';
        $shortcodeStart = '[' . $shortcodeName . $space . trim($attributes) . ']';
        $shortcodeEnd = empty($enclosedContent) && !$forceEnclosingForm ? '' : $enclosedContent . '[/' . $shortcodeName . ']';

        $newContent .= $shortcodeStart . $shortcodeEnd;
      } else if($childNode->nodeType === XML_ELEMENT_NODE && $childNode->hasChildNodes()){
        //Extract childnode tags and replace inner html
        $tagPattern = '/^(<'.$childNode->nodeName.'[^<>]*>)(.*)(<\/'.$childNode->nodeName.'>)$/s';
        $html = $doc->saveHTML($childNode);
        $hasMatch = preg_match ($tagPattern, $html, $matches);

        if($hasMatch){
          $innerContent = $this->replaceShortcodeNodesRecursive($doc, $childNode->childNodes, $savedShortcodes);
          $newContent .= $matches[1] . $innerContent . $matches[3];
        }else{
          $newContent .= $doc->saveHTML($childNode);
        }
      } else {
        $newContent .= $doc->saveHTML($childNode);
      }
    }

    return $newContent;
  }

  /**
   * @param $attributes
   * @param $translatableShortcodeAttributes
   * @return string
   */
  private function getAttributeNodes($attributes, $translatableShortcodeAttributes)
  {
    if(!is_array($attributes)){
      return '';
    }

    $attributeNodes = '';

    foreach ($attributes as $name => $value) {
      $isTranslatable = false;
      $encodeWith = '';

      foreach ($translatableShortcodeAttributes as $translatableShortcodeAttribute) {
        if ($translatableShortcodeAttribute['name'] == $name) {
          $isTranslatable = true;
          $encodeWith = $translatableShortcodeAttribute['encoding'];
        }
      }

      if ($isTranslatable) {
        $spanContent = empty($encodeWith) ? $value : $this->decodeEnclosedContent($value, $encodeWith);
        $attributeNodes .= '<span name="' . $name . '">' . $spanContent . '</span>';
      } else {
        $attributeNodes .= '<input type="hidden" name="' . $name . '" value="' . $value . '" />';
      }
    }
    return $attributeNodes;
  }

  /**
   * @param $name
   * @param $nodeValue
   * @param $translatableShortcodeAttributes
   * @return string
   */
  private function getAttributeValue($name, $nodeValue, $translatableShortcodeAttributes)
  {
    foreach ($translatableShortcodeAttributes as $translatableShortcodeAttribute) {
      if ($translatableShortcodeAttribute['name'] == $name) {
        return $this->encodeEnclosedContent($nodeValue, $translatableShortcodeAttribute['encoding']);
      }
    }
  }

  private function decodeEnclosedContent($content, $contentEncoding)
  {
    $functions = array_reverse(explode(',', $contentEncoding));

    foreach ($functions as $function) {
      switch (trim($function)) {
        case 'base64':
          $content = base64_decode($content);
          break;
        case 'url':
          $content = urldecode($content);
          break;
        case 'rawurl':
          $content = rawurldecode($content);
          break;
      }
    }

    return $content;
  }

  private function encodeEnclosedContent($content, $contentEncoding)
  {
    $functions = explode(',', $contentEncoding);

    foreach ($functions as $function) {
      switch (trim($function)) {
        case 'base64':
          $content = base64_encode($content);
          break;
        case 'url':
          $content = urlencode($content);
          break;
        case 'rawurl':
          $content = rawurlencode($content);
          break;
      }
    }

    return $content;
  }
}