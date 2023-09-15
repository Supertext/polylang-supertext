<?php

namespace Supertext\Helper;

/**
 * Class TextProcessor
 * @package Supertext\Helper
 */
class TextProcessor
{
  const SHORTCODE_TAG = 'div';
  const SHORTCODE_TAG_CLASS = 'supertext-shortcode';
  const SHORTCODE_CLOSED_TAG_CLASS = 'supertext-shortcode-closed';
  const SHORTCODE_ENCLOSED_CONTENT_CLASS = 'supertext-shortcode-enclosed';

  /**
   * @var Library
   */
  private $library;

  /**
   * @var array save shortcodes cached for one replacement process
   */
  private $cachedSavedShortcodes;

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
    try {
      $shortcodeSettings = $this->library->getSettingOption(Constant::SETTING_SHORTCODES);

      if (isset($shortcodeSettings['isShortcodeReplacementDisabled']) && $shortcodeSettings['isShortcodeReplacementDisabled']) {
        return $content;
      }

      $this->cachedSavedShortcodes = $shortcodeSettings['shortcodes'];
      $regex = $this->getExtendedShortcodeRegex();
      $excludedPositions = $this->getExcludedPositions($content);
      $callback = function ($match) use ($excludedPositions) {
        return $this->replaceShortcode($match, $excludedPositions);
      };

      $result = preg_replace_callback("/$regex/s", $callback, $content, -1, $count, PREG_OFFSET_CAPTURE);

      return $result === null ? $content : $result; //just return content if preg_replace_callback fails, shortcode replacement is not critical
    } catch (\Exception $e) {
      return $content;
    }
  }

  /**
   * Effectively replaces one shortcode with a html node.
   * @param $match matches (0: match, 1, 6: escaping chars, 2: shortcode tag name, 3: attributes, 5: enclosed content/data)
   * @param $savedShortcodes saved shortcodes
   * @return string replacement string
   */
  public function replaceShortcode($matchWithOffset, $excludedPositions)
  {
    $matchOffset = $matchWithOffset[0][1];

    if ($this->isExcluded($matchOffset, $excludedPositions)) {
      return $matchWithOffset[0][0];
    }

    $match = array_map(function ($match) {
      return $match[0];
    }, $matchWithOffset);

    //return escaped shortcodes, do not replace
    if ($match[1] == '[' && $match[6] == ']') {
      return $match[0];
    }

    $tagName = $match[2];
    $attributes = shortcode_parse_atts($match[3]);
    $shortcodeSetting = $this->getShortcodeSetting($tagName);
    $translatableShortcodeAttributes = $shortcodeSetting['attributes'];
    $forceEnclosingForm = preg_match('/\[\s*\/\s*' . $tagName . '\s*\]/', $match[0]);

    $attributeNodes = $this->getAttributeNodes($attributes, $translatableShortcodeAttributes);

    //Enclosed content can contain shortcodes as well
    if (!empty($match[5])) {
      $content = $match[5];
      if (!empty($shortcodeSetting['content_encoding'])) {
        $encoding = $shortcodeSetting['content_encoding'];
        $content = isset($shortcodeSetting['content_encoding_inverted']) && $shortcodeSetting['content_encoding_inverted'] ?
          $this->encodeEnclosedContent($content, $encoding) :
          $this->decodeEnclosedContent($content, $encoding);
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
    $shortcodeSettings = $this->library->getSettingOption(Constant::SETTING_SHORTCODES);
    $savedShortcodes = $shortcodeSettings['shortcodes'];

    $doc = $this->library->createHtmlDocument($content);

    $childNodes = $doc->getElementsByTagName('body')->item(0)->childNodes;

    return $this->replaceShortcodeNodesRecursive($doc, $childNodes, $savedShortcodes);
  }

  /**
   * Returns positions in the content where shortcodes should not be replaced. In this case the positions of Gutenberg block attributes or shortcodes in comments.
   */
  private function getExcludedPositions($content)
  {
    $excludedPositions = array();

    if (preg_match_all('/<!--\s+((wp:.*)|(\[[^\]]+\]([^\[]+\[[^\]]+\])?)(\s+)?)-->/', $content, $matches, PREG_OFFSET_CAPTURE)) {
      foreach ($matches[0] as $match) {
        $excludedPositions[] = array('start' => $match[1], 'end' => $match[1] + strlen($match[0]));
      }
    }

    return $excludedPositions;
  }

  /**
   * Checks if match should is excluded by its position.
   */
  private function isExcluded($matchOffset, $excludedPositions)
  {
    foreach ($excludedPositions as $excludedPosition) {
      if ($matchOffset >= $excludedPosition['start'] && $matchOffset <= $excludedPosition['end']) {
        return true;
      }
    }

    return false;
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

      if (
        $childNode->nodeType === XML_ELEMENT_NODE
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
                $encoding = $savedShortcodes[$shortcodeName]['content_encoding'];
                $enclosedContent = isset($savedShortcodes[$shortcodeName]['content_encoding_inverted']) && $savedShortcodes[$shortcodeName]['content_encoding_inverted'] ?
                  $this->decodeEnclosedContent($enclosedContent, $encoding) :
                  $this->encodeEnclosedContent($enclosedContent, $encoding);
              }

              break;
          }
        }

        $space = empty($attributes) ? '' : ' ';
        $shortcodeStart = '[' . $shortcodeName . $space . trim($attributes) . ']';
        $shortcodeEnd = empty($enclosedContent) && !$forceEnclosingForm ? '' : $enclosedContent . '[/' . $shortcodeName . ']';

        $newContent .= $shortcodeStart . $shortcodeEnd;
      } else if ($childNode->nodeType === XML_ELEMENT_NODE && $childNode->hasChildNodes()) {
        //Extract childnode tags and replace inner html
        $tagPattern = '/^(<' . $childNode->nodeName . '[^<>]*>)(.*)(<\/' . $childNode->nodeName . '>)$/s';
        $html = $doc->saveHTML($childNode);
        $hasMatch = preg_match($tagPattern, $html, $matches);

        if ($hasMatch) {
          $innerContent = $this->replaceShortcodeNodesRecursive($doc, $childNode->childNodes, $savedShortcodes);
          $newContent .= $matches[1] . $innerContent . $matches[3];
        } else {
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
    if (!is_array($attributes)) {
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

  /**
   * Gets an extended shortcode regex with the shortcodes that have been saved with shortcode settings
   * @return mixed|string
   */
  private function getExtendedShortcodeRegex()
  {
    $registeredShortcodes = $this->library->getShortcodeTags();
    $regex = get_shortcode_regex();

    $extendRegex = "";
    foreach ($this->cachedSavedShortcodes as $name => $savedShortcode) {
      if (isset($registeredShortcodes[$name])) {
        continue;
      }

      $extendRegex .= $name . '|';
    }

    $regex = str_replace('\[(\[?)(', '\[(\[?)(' . $extendRegex, $regex);
    return $regex;
  }

  /**
   * @param $tagName
   * @return array
   */
  private function getShortcodeSetting($tagName)
  {
    $shortcodeSetting = array('attributes' => array());

    foreach ($this->cachedSavedShortcodes as $name => $savedShortcodeSetting) {
      if (preg_match('/' . $name . '/', $tagName)) {
        $shortcodeSetting = $savedShortcodeSetting;
      }
    }

    return $shortcodeSetting;
  }
}
