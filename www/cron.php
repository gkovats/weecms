<?php 

  if (php_sapi_name() != "cli") {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /"); 
    exit;
  }
  
  require_once('common.inc.php');
  $cache = Cache::getInstance();
  $cache->clear();

  $time = time();
  echo "\n($time) Cleared site cache\n\n";