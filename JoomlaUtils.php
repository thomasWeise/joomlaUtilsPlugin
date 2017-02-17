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
  const BR = [ '<br>','<br/>','<br />',"<br\t>",'<p/>','<p />' ];
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
      'en' => 'https://en.wikipedia.org/wiki/','zh' => 'https://zh.wikipedia.org/wiki/',
      'ru' => 'https://ru.wikipedia.org/wiki/' ];
  
  // constants for math constants to be replaced
  const MATH_IN = [ '\infty','\in','\gt','\lt','\ge','\le','\approx','\neq','\star',
      '\forall','\exists','\not\in','\Rightarrow','\mapsto','\prime','&prime;&prime;',
      '\emptyset','\sqrt','\land','\lor','\times','\dots','\ ',' ','\sum' ];
  // constants for math constants to replac
  const MATH_OUT = [ '&infin;','&isin;','&gt;','&lt;','&ge;','&le;','&asymp;','&ne;',
      '&lowast;','&forall;','&exist;','&notin;','&rArr;','&#x21a6;','&prime;','&Prime;',
      '&empty;','&radic;','&and;','&or;','&times;','&hellip;','&nbsp;','',
      '<span class="big">&sum;</span>' ];
  
  // all possible leading breaks
  static $BREAKS_LEADING;
  // all possible trailing breaks
  static $BREAKS_TRAILING;
  // all possible breaks
  static $BREAKS_ALL;
  
  // the main method of the plugin invoked by Joomla
  public function onContentPrepare($context, &$article, &$params, $limitstart) {
    $changed = false;
    
    if (self::__renderMath ( $article->text )) {
      $changed = true;
    }
    if (self::__renderMap ( $article->text )) {
      $changed = true;
    }
    if (self::__renderSecondLanguage ( $article->text )) {
      $changed = true;
    }
    if (self::__renderTOC ( $article->text )) {
      $changed = true;
    }
    if ($changed) {
      JFactory::getDocument ( )->addStyleSheet ( 
          JURI::base ( ) . "plugins/content/JoomlaUtils/css/style.css" );
    }
    return true;
  }
  
  // rendering math tags
  private function __renderMath(&$text) {
    $offset = 0;
    $retval = false;
    while ( (($startIndex = strpos ( $text, '$$', $offset )) !== false) &&
         ($startIndex >= $offset) ) {
          $offset = $startIndex + 2;
      if ((($endIndex = strpos ( $text, '$$', $startIndex + 2 )) !== false) &&
           ($endIndex > $startIndex)) {
        $text = substr_replace ( $text, 
            ('<span class="math">' . self::__renderSingleMath ( 
                str_replace ( self::MATH_IN, self::MATH_OUT, 
                    substr ( $text, $startIndex + 2, $endIndex - $startIndex - 2 ) ) ) .
             '</span>'), $startIndex, $endIndex - $startIndex + 2 );
        $retval = true;
      } else {
        return $retval;
      }
    }
    
    return $retval;
  }
  
  // rendering math recursively
  private function __renderSingleMath($text) {
    $len = strlen ( $text );
    $result = '';
    $braceStart = false;
    $braceDepth = 0;
    $copyFrom = 0;
    $next = '';
    $insert = '';
    
    for($i = 0; $i < $len; $i ++) {
      $curChar = $text [$i];
      
      if ($curChar === '{') {
        if ($braceDepth === 0) {
          $braceStart = $i;
        }
        $braceDepth += 1;
        continue;
      }
      
      if ($curChar === '}') {
        $braceDepth -= 1;
        if ($braceDepth === 0) {
          $result = $result . substr ( $text, $copyFrom, $braceStart - $copyFrom ) . self::__renderSingleMath ( 
              substr ( $text, $braceStart + 1, $i - $braceStart - 1 ) );
          if (strlen ( $next ) > 0) {
            $result .= $next;
            $next = '';
          }
          $copyFrom = $i + 1;
        }
        continue;
      }
      
      if ($braceDepth > 0) {
        continue;
      }
      
      switch ($curChar) {
        case '^' :
          {
            $insert .= '<sup>';
            $next = '</sup>' . $next;
            break;
          }
        case '_' :
          {
            $insert .= '<sub>';
            $next = '</sub>' . $next;
            break;
          }
        case '~' :
          {
            $insert .= '<span class="vec">';
            $next = '</span>' . $next;
            break;
          }
        case '@' :
          {
            $insert .= '<span class="msp">';
            $next = '</span>' . $next;
            break;
          }
        default :
          {
            if (strlen ( $next ) > 0) {
              $result .= substr ( $text, $copyFrom, $i - $copyFrom + 1 ) . $next;
              $next = '';
              $copyFrom = $i + 1;
            }
            continue 2;
          }
      }
      
      $result = $result . substr ( $text, $copyFrom, $i - $copyFrom ) . $insert;
      $insert = '';
      $copyFrom = $i + 1;
    }
    
    return $result . substr ( $text, $copyFrom ) . $next;
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
        
        $text = self::__replaceAndFixBreaks ( $text, $startIndex, $endIndex + 5, $mapRes );
        $retval = true;
      } else {
        return $retval;
      }
    }
    
    return $retval;
  }
  
  // render a toc: pick up <h.. tags, number them, give them ids, list them
  private function __renderTOC(&$text) {
    if (strpos ( $text, '{toc}' ) === false) {
      return false;
    }
    
    $toc = '';
    $counters = array (0,0,0,0,0,0,0 );
    
    $startIndex = 0;
    while ( ($startIndex = strpos ( $text, '<h', $startIndex )) !== false ) {
      $startIndex += 2;
      $depth = substr ( $text, $startIndex, 1 );
      if (strlen ( $depth ) !== 1) {
        break;
      }
      $startIndex ++;
      $depth = intval ( $depth );
      if (($depth <= 0) || ($depth > 6)) {
        continue;
      }
      
      $inner = substr ( $text, $startIndex, 1 );
      if ($inner === '>') {
        $innerStart = $innerEnd = $startIndex;
        $inner = '';
        $startIndex ++;
      } else {
        if ($inner !== ' ') {
          continue;
        }
        $startIndex ++;
        if ((($innerEnd = strpos ( $text, '>', $startIndex )) === false) ||
             ($innerEnd < $startIndex)) {
          continue;
        }
        $inner = substr ( $text, $startIndex, $innerEnd - $startIndex );
        $innerStart = $startIndex;
        $startIndex = $innerEnd + 1;
      }
      
      if ((($endIndex = strpos ( $text, '</h' . $depth . '>', $startIndex )) === false) ||
           ($endIndex < $startIndex)) {
        continue;
      }
      $contents = substr ( $text, $startIndex, $endIndex - $startIndex );
      $contentStart = $startIndex;
      $startIndex = $endIndex + 5;
      
      // find key
      $keyStr = '';
      $useDepth = 1;
      $hasKey = false;
      for(; $useDepth < $depth; $useDepth ++) {
        if (($counters [$useDepth] > 0) || $hasKey) {
          if ($hasKey) {
            $keyStr = $keyStr . '.';
          }
          $hasKey = true;
          $keyStr = $keyStr . $counters [$useDepth];
        }
      }
      
      if ($hasKey) {
        $keyStr = $keyStr . '.';
      }
      $counters [$depth] ++;
      $keyStr = $keyStr . $counters [$depth];
      $keyStr = $keyStr . '.';
      
      for($useDepth = ($depth + 1); $useDepth < count ( $counters ); $useDepth ++) {
        $counters [$useDepth] = 0;
      }
      
      // determine id
      $id = $keyStr;
      $needsId = true;
      if (($idStart = strpos ( $inner, 'id=' )) !== false) {
        $idStart += 3;
        $idSep = substr ( $inner, $idStart, 1 );
        if (($idSep === '"') || ($idSep == "'")) {
          $idStart ++;
          if ((($idEnd = strpos ( $inner, $idSep, $idStart )) !== false) &&
               ($idEnd > $idStart)) {
            $id = substr ( $inner, $idStart, ($idEnd - $idStart) );
            $needsId = false;
          }
        }
      }
      
      $keyStr = $keyStr . '&nbsp;';
      $text = substr_replace ( $text, $keyStr, $contentStart, 0 );
      $startIndex += strlen ( $keyStr );
      
      if ($needsId) {
        $text = substr_replace ( $text, (' id="' . $id . '"'), $innerStart, 0 );
        $startIndex += 6 + strlen ( $id );
      }
      
      $toc = $toc . '<br />';
      for($useDepth = 1; $useDepth < $depth; $useDepth ++) {
        if ($counters [$useDepth] > 0) {
          $toc = $toc . '&nbsp;&nbsp;';
        }
      }
      
      $toc = $toc . '<a href="#' . $id . '" class="toc">' . $keyStr . trim ( $contents ) .
           '</a>';
    }
    
    if (($startIndex = strpos ( $text, '{toc}' )) !== false) {
      if (strlen ( $toc ) > 0) {
        $text = self::__replaceAndFixBreaks ( $text, $startIndex, $startIndex + 5, 
            '<div class="tocOuter"><div class="tocInner"><span class="tocTitle">Contents</span>' .
                 $toc . '</div></div>' );
        return true;
      }
    }
    
    return false;
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
  
  // replace a portion in a text and fix potential leading and trailing breaks
  private function __replaceAndFixBreaks($text, $startIndex, $endIndex, $replacement) {
    $preText = substr ( $text, 0, $startIndex );
    $postText = substr ( $text, $endIndex );
    
    $found = true;
    while ( $found ) {
      while ( $found ) {
        $found = false;
        $preText = rtrim ( $preText );
        $postText = ltrim ( $postText );
        
        foreach ( self::BR as $str ) {
          $i = strlen ( $str );
          if (substr ( $postText, 0, $i ) === $str) {
            $postText = substr ( $postText, $i );
            $found = true;
          }
          $i = strlen ( $preText ) - $i;
          if (substr ( $preText, $i ) === $str) {
            $preText = substr ( $preText, 0, $i );
            $found = true;
          }
        }
      }
      
      $pOpen = false;
      foreach ( self::P_OPEN as $str ) {
        $i = strlen ( $preText ) - strlen ( $str );
        if (substr ( $preText, $i ) === $str) {
          $pOpen = $i;
          break;
        }
      }
      
      if ($pOpen !== false) {
        $pClose = false;
        
        foreach ( self::P_CLOSE as $str ) {
          $i = strlen ( $str );
          if (substr ( $postText, 0, $i ) === $str) {
            $pClose = $i;
            break;
          }
        }
        
        if ($pClose !== false) {
          $preText = substr ( $preText, 0, $pOpen );
          $postText = substr ( $postText, $pClose );
          $found = true;
        }
      }
    }
    
    return $preText . $replacement . $postText;
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

// all possible breaks
plgContentJoomlaUtils::$BREAKS_ALL = array_merge ( plgContentJoomlaUtils::BR, 
    plgContentJoomlaUtils::P_OPEN, plgContentJoomlaUtils::P_CLOSE );
?>
