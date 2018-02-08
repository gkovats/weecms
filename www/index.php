<?php

  $timer  = microtime();
  
  require_once('common.inc.php');
  
  $router = Router::getInstance();

  echo "<!-- Render time: ". (microtime() - $timer). " -->\n";
  
  
