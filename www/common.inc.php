<?php 

// Define import directories
define('ROOT',      str_replace('\\','/',__DIR__));
define('DIR_CSS',   ROOT . '/_/css');
define('DIR_JS',    ROOT . '/_/js');
define('DIR_TMPL',  ROOT . '/_/tmpl');
define('DIR_IMG',   ROOT . '/_/img');

// in case it's not set in PHP.ini
date_default_timezone_set('America/New_York');

// Include Composer packages
require(ROOT . '/../vendor/autoload.php');

require(ROOT . '/bin/config.php');
require(ROOT . '/bin/template.php');
require(ROOT . '/bin/cache.php');
require(ROOT . '/bin/posts.php');
require(ROOT . '/bin/router.php');
