<?php
/**
 *
 * @version $Id: plgContentJoomlaUtils.php 0.8.0 2016-12-22 Thomas Weise $
 * @package JoomlaUtils
 * @copyright Copyright (C) 2016 Thomas Weise. All rights reserved.
 * @license GNU/GPL, see LICENSE.php
 */
defined ( '_JEXEC' ) or die ( 'Restricted access' );
class plgContentJoomlaUtils extends JPlugin {
  
  // the colors for the maps
  const MAP_COLORS = [ '0000ff','00ff00','ff0000','00ffff','ff00ff','ffff00','000088',
      '008800','880000','008888','880088','888800' ];
  
  // an array with characters to be replaced by space
  const TO_SPACE = [ "\f","\t","\x0B","\0",'  ','  ','  ','  ' ];
  // constants for line breaks
  const BR = [ '<br>','<br/>','<br />',"<br\t>" ];
  // constants for opening paragraph tags
  const P_OPEN = [ '<p>' ];
  // constants for closing paragraph tags
  const P_CLOSE = [ '</p>' ];
  // a prefix for wikipedia links
  const WIKIPEDIA_PREFIX = 'wiki';
  // the chinese locale
  const LOCALE_CHINESE = 'zh_HANS';
  // the default locale
  const LOCALE_DEFAULT = 'en';
  // the chinese locale
  const LOCALE_FOREIGN_DEFAULT = self::LOCALE_CHINESE;
  // the wikipedia base urls for different locales
  const WIKIPEDIA_URLS_FOR_LOCALES = [ 'de' => 'https://de.wikipedia.org/wiki/',
      'en' => 'https://en.wikipedia.org/wiki/','zh' => 'https://zh.wikipedia.org/wiki/' ];
  
  // all possible leading breaks
  static $BREAKS_LEADING;
  // all possible trailing breaks
  static $BREAKS_TRAILING;
  // all possible breaks
  static $BREAKS_ALL;
  
  // the main method of the plugin invoked by Joomla
  public function onContentPrepare($context, &$article, &$params, $limitstart) {
    $changed = false;
    
    while ( self::__renderMap ( $article->text ) ||
         self::__renderSecondLanguage ( $article->text ) ) {
          $changed = true;
    }
    
    if ($changed) {
      JFactory::getDocument ( )->addStyleSheet ( 
          JURI::base ( ) . "plugins/content/JoomlaUtils/css/style.css" );
    }
    
    return true;
  }
  
  // rendering second language tags and wikipedia links [[..]]
  private function __renderSecondLanguage(&$text) {
    $offset = 0;
    $retval = false;
    while ( (($startIndex = strpos ( $text, '[[', $offset )) !== false) &&
         ($startIndex >= $offset) ) {
          $offset = $startIndex + 2;
      if ((($endIndex = strpos ( $text, ']]', $startIndex + 2 )) !== false) &&
           ($endIndex > $startIndex)) {
        
        $selected = explode ( '|', 
            trim ( substr ( $text, $startIndex + 2, $endIndex - $startIndex - 2 ) ) );
        
        $contents = trim ( $selected [0] );
        
        $locale = (count ( $selected ) > 1) ? trim ( $selected [1] ) : '';
        if (strlen ( $locale ) <= 0) {
          $locale = self::LOCALE_FOREIGN_DEFAULT;
        }
        
        $url = (count ( $selected ) > 2) ? trim ( $selected [2] ) : '';
        if ($url === self::WIKIPEDIA_PREFIX) {
          $url = $contents;
        }
        
        if ($locale !== self::LOCALE_DEFAULT) {
          $replacementStart = '<span class="lng" lang="' . $locale . '">[';
          $replacementEnd = ']</span>';
        } else {
          $replacementStart = '';
          $replacementEnd = '';
        }
        if (strlen ( $url ) > 0) {
          if (substr ( $url, 0, 4 ) !== 'http') {
            $url = self::__wikiLink ( $url, $locale );
          }
          $replacementStart = $replacementStart . '<a href="' . $url . '">';
          $replacementEnd = '</a>' . $replacementEnd;
        }
        
        $text = substr_replace ( $text, 
            ($replacementStart . $contents . $replacementEnd), $startIndex, 
            $endIndex - $startIndex + 2 );
        $retval = true;
      } else {
        return $retval;
      }
    }
    
    return $retval;
  }
  
  // generate a link to wikipedia
  private function __wikiLink($text, $locale = self::LOCALE_DEFAULT) {
    
    // repare wikipedia url body
    $text = trim ( $text );
    $i = strlen ( self::WIKIPEDIA_PREFIX );
    if (substr ( $text, 0, $i ) === self::WIKIPEDIA_PREFIX) {
      $text = trim ( substr ( $text, $i ) );
    }
    if (substr ( $text, 0, 1 ) === ':') {
      $text = trim ( substr ( $text, 1 ) );
    }
    $text = str_replace ( ' ', '_', $text );
    
    // prepare locale
    $locale = trim ( $locale );
    $i = strpos ( $locale, '_' );
    if ($i !== false) {
      $locale = trim ( substr ( $locale, 0, $i ) );
    }
    
    // lookup base url
    if (array_key_exists ( $locale, self::WIKIPEDIA_URLS_FOR_LOCALES )) {
      $baseUrl = self::WIKIPEDIA_URLS_FOR_LOCALES [$locale];
    } else {
      $baseUrl = self::WIKIPEDIA_URLS_FOR_LOCALES [self::LOCALE_DEFAULT];
    }
    return $baseUrl . $text;
  }
  
  // rendering of map shortcodes
  private function __renderMap(&$text) {
    $offset = 0;
    $retval = false;
    
    while ( (($startIndex = strpos ( $text, '{map}', $offset )) !== false) &&
         ($startIndex >= $offset) ) {
          
          $offset = $startIndex + 5;
      
      if ((($endIndex = strpos ( $text, '{map}', $startIndex + 5 )) !== false) &&
           ($endIndex > $startIndex)) {
        
        $mapLines = explode ( PHP_EOL, 
            self::__convertBreaks ( 
                substr ( $text, $startIndex + 5, $endIndex - $startIndex - 5 ) ) );
        
        $mapRes = '<div class="map"><ul class="map">';
        
        $index = 0;
        $altImage = self::__nextStringFromArray ( $mapLines, $index );
        $altTxt = self::__nextStringFromArray ( $mapLines, $index );
        $markers = '';
        $locationIndex = 0;
        while ( ($line = self::__nextStringFromArray ( $mapLines, $index )) !== false ) {
          $i = strpos ( $line, '|' );
          $color = self::MAP_COLORS [$locationIndex];
          $id = chr ( 65 + $locationIndex );
          $coordinate = trim ( substr ( $line, 0, $i ) );
          $mapRes = $mapRes . '<li class="map"><a style="color:#' . $color .
               '" href="http://maps.google.com/maps?q=' . $coordinate . '">' . $id .
               '</a>:&nbsp;' . trim ( substr ( $line, $i + 1 ) ) .
               ' (<a href="http://maps.google.com/maps?q=' . $coordinate .
               '">map</a>)</li>';
          $markers = $markers . '&amp;markers=color:0x' . $color . '%7Clabel:' . $id .
               '%7C' . $coordinate;
          $locationIndex = ($locationIndex + 1);
        }
        
        $mapRes = $mapRes .
             '</ul><p class="map"><img src="http://maps.googleapis.com/maps/api/staticmap?size=690x690&amp;maptype=roadmap&amp;format=png&amp;language=language&amp;sensor=false' .
             $markers . '" alt="' . $altTxt .
             '" style="min-width:100%;width:100%;max-width:100%;min-height:auto;height:auto;max-height:auto" onError="this.onerror=null;this.src=' .
             "'" . $altImage . "'" . ';" /></p></div>';
        
        $text = self::__stripTrailingBreaks ( substr ( $text, 0, $startIndex ) ) . $mapRes . self::__stripLeadingBreaks ( 
            substr ( $text, $endIndex + 5 ) );
        $retval = true;
      } else {
        return $retval;
      }
    }
    
    return $retval;
  }
  
  // finding next non-empty string in a string list
  private function __nextStringFromArray($array, &$index) {
    for(; $index < count ( $array ); $index ++) {
      $text = trim ( $array [$index] );
      if (strlen ( $text ) > 0) {
        $index ++;
        return $text;
      }
    }
    return false;
  }
  
  // remove breaks directly at the end of text that may come from tinyMCE
  private function __stripTrailingBreaks($text) {
    $found = true;
    while ( $found ) {
      $found = false;
      $text = rtrim ( $text );
      foreach ( self::$BREAKS_TRAILING as $str ) {
        $i = strlen ( $text ) - strlen ( $str );
        if (substr ( $text, $i ) === $str) {
          $text = substr ( $text, 0, $i );
          $found = true;
        }
      }
    }
    return $text;
  }
  
  // remove breaks directly at the start of text that may come from tinyMCE
  private function __stripLeadingBreaks($text) {
    $found = true;
    while ( $found ) {
      $found = false;
      $text = ltrim ( $text );
      foreach ( self::$BREAKS_LEADING as $str ) {
        $i = strlen ( $str );
        if (substr ( $text, 0, $i ) === $str) {
          $text = substr ( $text, $i );
          $found = true;
        }
      }
    }
    
    return $text;
  }
  
  // convert breaks to newlines in order to deal with stuff tinyMCE might have done
  private function __convertBreaks($text) {
    $found = true;
    
    while ( $found ) {
      $found = false;
      
      $count = 0;
      $text = trim ( $text );
      $text = str_replace ( self::TO_SPACE, ' ', $text, $count );
      if ($count > 0) {
        $found = true;
        $count = 0;
      }
      $text = str_replace ( PHP_EOL . PHP_EOL, PHP_EOL, $text, $count );
      if ($count > 0) {
        $found = true;
        $count = 0;
      }
      $text = trim ( $text );
      
      $text = str_replace ( self::$BREAKS_ALL, PHP_EOL, $text, $count );
      if ($count > 0) {
        $found = true;
        $count = 0;
      }
    }
    return $text;
  }
}

// all possible leading breaks
plgContentJoomlaUtils::$BREAKS_LEADING = array_merge ( plgContentJoomlaUtils::BR, 
    plgContentJoomlaUtils::P_CLOSE );
// all possible trailing breaks
plgContentJoomlaUtils::$BREAKS_TRAILING = array_merge ( plgContentJoomlaUtils::BR, 
    plgContentJoomlaUtils::P_OPEN );
// all possible breaks
plgContentJoomlaUtils::$BREAKS_ALL = array_merge ( plgContentJoomlaUtils::BR, 
    plgContentJoomlaUtils::P_OPEN, plgContentJoomlaUtils::P_CLOSE );
?>