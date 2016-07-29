<?php

//This is just a basic invoker for the tidepools class.

error_reporting(E_ALL);
ini_set('display_errors', 1);

//For this simple case no namespacing or autoloader has been setup.
require_once(realpath(dirname(__FILE__)).'/src/tidepools.php');

$ops = array_values($argv);

//Remove the file name
array_shift($ops);

$poolsObj = new TidePools ();

//What are we being asked to test?
switch ( count( $ops ) ) {
 case 0:
   $chars = range(0,9);
   $str = '';
   for ($i = 0; $i < TidePools::MAX_GEO_LEN; $i++) {
      $str .= $chars[mt_rand(0, count($chars) - 1)];
   } 
   //If you want random coral as an option uncomment below
   //$poolsObj->coral((bool) mt_rand(0,1));
   break;
 case 1: 
   $str = array_shift( $ops );
   break;
 default:
   $fileParts = explode('/',__FILE__);
   $file = array_pop($fileParts);
   $usage =<<<STR
Usage: php {$file} STRING_OF_NUMBERS
STR;
   echo $usage.PHP_EOL;
   return;
}
$poolsObj->loadGeography( $str );
$poolsObj->printGeography();
echo "There are ".$poolsObj->getVolume()." units of water in the described pool(s).".PHP_EOL;
