<?php

class TidePools {
   
   const COLOR_ATTR_NONE = 0;
   const COLOR_ATTR_BRGT = 1;

   const PRINT_WIDTH = 2;

   const MIN_GEO_LEN = 3;
   const MAX_GEO_LEN = 45;

   //The tile types
   const TILE_SKY   = 0;
   const TILE_LAND  = 1;
   const TILE_WATER = 2;
   
   protected $volume = 0;
   protected $printCoral = FALSE;
   protected $printWidth = self::PRINT_WIDTH;
   protected $printSeqs = [];

   public function coral ( $value ) {
      if (!is_bool($value)) {
         throw new InvalidArgumentException ( 'You must pass a boolena value to '.__METHOD__ );
      }
      $this->printCoral = $value;
   }

   public function printWidth ( $width = NULL) {
      if ( $width === NULL ) {
         return $this->printWidth;
      } else {
         if ( ( ! is_int( $width ) ) || $width < 1 ) {
            throw new Exception ('Print width must be a positive integer.');
         }
         $this->printWidth = $width;
      }
   }

   public function loadGeography ( $geographyStr ) {
      if ( ! preg_match('/^[0-9]{'.self::MIN_GEO_LEN.','.self::MAX_GEO_LEN.'}$/', $geographyStr ) ) {
         throw new InvalidArgumentException( 'Unable to validate geography string passed.' );
      }
      $this->geography = str_split( $geographyStr );
      $this->flood();
   }

   public function printGeography () {
      //Build our print sequences
      foreach ([self::TILE_SKY,self::TILE_LAND,self::TILE_WATER] as $tile) {
         $this->printSeqs[$tile] = str_repeat($tile, $this->printWidth);
      }
      //Process the geography
      foreach ($this->lines as $i => $lineArr) {
         $numCols = count($lineArr);
         $formatStr = str_repeat('% 4s', $numCols);
         $charArr = array_values($lineArr);
         $args = array_map( function ($v) { 
            switch ( $v ) {
             case self::TILE_WATER: //Water
               $str = $this->colorStr($this->printSeqs[self::TILE_WATER],self::COLOR_ATTR_NONE,'blue','blue');
               break;
             case self::TILE_LAND: //Land
               if ($this->printCoral && mt_rand(1,2) == 2) {
                  $attr = self::COLOR_ATTR_BRGT;
                  $coralColors = ['red','yellow','green','blue','white','green'];
                  $coralChars = ['*','%','@','&'];
                  $str = '';
                  for ($j = 0; $j < $this->printWidth; $j++) {
                     $fgColor = $coralColors[mt_rand(0,count($coralColors)-1)];
                     $char = $coralChars[mt_rand(0,count($coralChars)-1)];
                     $str .= $this->colorStr($char,$attr,$fgColor,'green');
                  }
               } else {
                  $str = $this->colorStr($this->printSeqs[self::TILE_LAND],self::COLOR_ATTR_NONE,'green','green');
               }
               break;
             case self::TILE_SKY: //Sky
               $attr = self::COLOR_ATTR_NONE;
               $fgColor = 'cyan';
               $bgColor = 'cyan';
               $str = $this->colorStr($this->printSeqs[self::TILE_SKY],$attr,$fgColor,$bgColor);
               break;
            }
            return $str; 
         }, $charArr);
         array_unshift($args, $formatStr);
         echo call_user_func_array('sprintf', $args).PHP_EOL;
      }
   }

   public function getVolume ( ) {
      return $this->volume;
   }

   protected function flood () {
      $colCount = count($this->geography);
      $lines = [];
      foreach ( $this->geography as $i => $v ) {
         $str = str_repeat(self::TILE_LAND, $v);
         $chars = str_split(sprintf('%'.self::TILE_SKY.'-10s', $str));
         //Always have land on bottom
         $lines[0][$i] = self::TILE_LAND;
         for ($x = 1; $x <= 9; $x++) {
            $lines[$x][$i] = (int) array_shift($chars);
         }
         //Always have sky on top
         $lines[10][$i] = self::TILE_SKY;
      }

      //Flood our pools
      foreach ( $lines as $layerNum => $colArr ) {
         //No work for sky or land
         if (in_array($layerNum,[0,10])) {
            continue;
         }
         $landPos = array_filter($colArr);
         $skyPos = array_filter($colArr, function ( $v ) {
            return ($v==self::TILE_SKY)?TRUE:FALSE;
         });
         //More than one land?
         if ( ( ! count($landPos) ) || ( ! count($skyPos) ) ) {
            continue;
         }
         $landKeys = array_keys($landPos);
         $skyKeys = array_keys($skyPos);
         $minLand = min($landKeys);
         $maxLand = max($landKeys);
         //Is there a gap in between?
         if ( ( $maxLand - $minLand ) <= 1 ) {
            continue;
         }
         $waterPos = array_filter($skyKeys, function ( $v ) use ($minLand, $maxLand) {
            if ( $v > $minLand && $v < $maxLand ) {
               return TRUE;
            }
            return FALSE;
         });
         foreach ($waterPos as $k) {
            $lines[$layerNum][$k] = self::TILE_WATER;
            $this->volume++;
         }
      }

      //Flip the world upside down
      $this->lines = array_reverse($lines);
   }

   protected function colorStr ( $string, $attr, $fgColor, $bgColor ) {
      if (! strlen($string)) {
         return '';
      }
      $open = '['.$attr.';';
      switch ($fgColor) {
       case 'black':  $open .= '30;'; break;
       case 'red':    $open .= '31;'; break;
       case 'green':  $open .= '32;'; break;
       case 'yellow': $open .= '33;'; break;
       case 'blue':   $open .= '34;'; break;
       case 'magent': $open .= '35;'; break;
       case 'cyan':   $open .= '36;'; break;
       case 'white':  $open .= '37;'; break;
       default:
         throw new Exception ( 'The following fgColor is not supported: '.$fgColor );
      }
      switch ($bgColor) {
       case 'black':  $open .= '40m'; break;
       case 'red':    $open .= '41m'; break;
       case 'green':  $open .= '42m'; break;
       case 'yellow': $open .= '43m'; break;
       case 'blue':   $open .= '44m'; break;
       case 'magent': $open .= '45m'; break;
       case 'cyan':   $open .= '46m'; break;
       case 'white':  $open .= '47m'; break;
       default:
         throw new Exception ( 'The following bgColor is not supported: '.$bgColor );
      }
      $close = '['.self::COLOR_ATTR_NONE.';m';

      return $open.$string.$close;
   }
}
