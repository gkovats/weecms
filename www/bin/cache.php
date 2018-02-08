<?php

// Can't load directly
if (!defined('ROOT')) {
    exit();
}
    
    // Define import directories
define('DIR_CACHE', ROOT . '/_/cache');

/**
 * Cache class
 */
class Cache
{
    
    // Class instance for Singleton
    private static $instance;
    
    // Config
    private $config;
    
    // Data
    private $data = array();
    
    // Constructor
    private function __construct()
    {
        $this->config   = Config::getInstance();
    }

    /**
     * Fetch instance of this class
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Cache();
        }
        return self::$instance;
    }
    
    /**
     * Get key for a given page name
     */
    private function cleanPageName($page)
    {
        $page   = str_replace($this->config->get('site.url'), '', $page);
        $page   = preg_replace(array('/^[\\/\\\\]+/','/[\\/\\\\]+$/'), '', $page);
        if (empty($page)) {
            $page = 'home';
        }
        return $page;
    }
    
    /**
     * Get key for a given page name
     */
    private function getPageKey($page)
    {
        $key    = substr(md5($page.TS), 0, 16);
        return $key;
    }
    
    /**
     * Render a cached page
     */
    public function renderPage($page, $exit = true)
    {
        // if not prod, fail here
        if ($this->config->get('site.env') != 'prod') {
            return false;
        }
        
        $page   = $this->cleanPageName($page);
        $key    = $this->getPageKey($page);
        $file   = DIR_CACHE."/$key.html";
        if (file_exists($file)) {
            echo file_get_contents($file);
            echo "\n<!-- Cached:$key -->\n";
            if ($exit) {
                exit;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Set a cached page
     */
    public function setPage($page, $data)
    {
        
        // if not prod, fail here
        if ($this->config->get('site.env') != 'prod') {
            return false;
        }
        
        $page   = $this->cleanPageName($page);
        $key    = $this->getPageKey($page);
        $file   = DIR_CACHE."/$key.html";
        
        // @TODO: possibility here for minification
        
        return file_put_contents($file, $data);
    }

    /**
     * Clear cache
     */
    public function clear()
    {
        array_map('unlink', glob(DIR_CACHE."/*.html"));
    }
}
