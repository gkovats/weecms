<?php

// Can't load directly
if (! defined('ROOT')) {
    exit();
}

/**
 * Config class
 */
class Config
{

    // static instance
    private static $instance;

    // Instance of Template
    private $template;

    // Instance of Posts
    private $posts;

    // config
    private $data;

    // current page
    public $page;

    // Constructor
    private function __construct()
    {
        // update pages
        $this->data = parse_ini_file(ROOT . '/../config.ini', true);

        // Env setting
        if ($this->get('site.env') == 'prod') {
            error_reporting(0);
        }
    }

    /**
     * Fetch instance of this class
     */
    public static function getInstance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    /**
     * Fetch from data by .
     * notation
     */
    public function get($field, $default = false)
    {
        // fetch config detail
        $keys = explode('.', $field);
        $data = $this->data;
        while (count($keys)) {
            $key = array_shift($keys);
            if (! isset($data [$key])) {
                break;
            }
            $data = $data [$key];
        }
        return $data;
    }

    /**
     * Check redirects
     */
    public function getRedirect($page)
    {
        if (isset($this->data['redirects'][$page])) {
            return $this->data['redirects'][$page];
        }
        return false;
    }
}
