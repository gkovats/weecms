<?php

// Can't load directly
if (!defined('ROOT')) {
    exit;
}

/**
 * Router class
 */
class Router
{

    // static instance
    private static $instance;

    // Instance of Template
    private $template;

    // Instance of Posts
    private $posts;

    // config
    private $config;

    // cache
    private $cache;

    // current page
    public $page;

    // Constructor
    private function __construct()
    {
        $page           = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $page           = preg_replace('/^\\/+/','', $page);
        
        // update pages
        $this->page     = $page;
        $this->config   = Config::getInstance();

        // This is a global timestamp used for cache busting
        if (!defined('TS')) {
            define('TS', filemtime(ROOT . '/../config.ini'));
        }
        if (!defined('CB')) {
            if ($this->config->get('site.env') == 'prod') {
                define('CB', substr((string)TS, -4) * 1);
            } else {
                define('CB', time());
            }
        }

        // get instance of Template
        $this->template = new Template();
        $this->posts    = Posts::getInstance();
        $this->cache    = Cache::getInstance();

        $this->route();
    }

    /**
     * Fetch instance of this class
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Router();
        }

        return self::$instance;
    }

    /**
     * route
     */
    public function route()
    {

        // template data
        $data = array(
            'page'  => array(
                'url'   => $this->config->get('site.url') . "/" . $this->page,
                'path'  => $this->page
            )
        );

        // check for redirect
        $redirect   = $this->config->getRedirect($this->page);
        if ($redirect) {
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: ".$this->config->get('site.url')."/$redirect");
            return;
        }

        // send mime type header
        $this->renderHeader();

        // new content! purge cache
        $cachebuster_key = $this->config->get('app.cb_key');
        if ($cachebuster_key && isset($_REQUEST[$cachebuster_key])) {
            $this->cache->clear();
        }

        // if we're able to find a cached page, return
        if ($this->cache->renderPage($this->page)) {
            return;
        }

        // else follow the routes
        // BEGIN ROUTE
        if ($this->page == '/' || $this->page == '') {

            // HOMEPAGE
            $count = $this->config->get('homepage.postscount');
            $data['page']['url']    = $this->config->get('site.url');
            $data['posts']          = $this->posts->getList($count);
            $data['homepage']       = true;
            return $this->render('homepage', $data);
        } elseif (preg_match('/^\d{8}\.[^\/]+$/', $this->page)) {

            // POST
            $post_id    = trim(strtolower($this->page));
            $post       = $this->posts->getPost($post_id);
            if (!$post) {
                return $this->error('404');
            }
            $data['post']   = $post;

            // Add page title
            if ($post['title']) {
                $data['page']['title']  = $post['title'];
            }
            // Add page url
            if ($post['url']) {
                $data['page']['url']    = $post['url'];
            }

            return $this->render('post', $data);
        } elseif (preg_match('/^tag\\/([^\\/]+)$/', $this->page, $matches)) {

            // TAG PAGE
            $tag    = false;
            if (count($matches) < 2) {
                return $this->error('404', $data);
            }
            $tag            = urldecode($matches[1]);
            $count          = $this->config->get('homepage.postscount');
            $posts          = $this->posts->getTagList($tag, $count);

            $data['tag']    = $tag;
            if (!count($posts)) {
                return $this->error('404', $data);
            }
            $data['page']['url']    = $this->config->get('site.url');
            $data['posts']          = $posts;
            return $this->render('tag', $data);
        } elseif ($this->page == 'feed.rss') {

            // RSS
            $data['page']['url']    = $this->config->get('site.url');
            $data['posts']          = $this->posts->getList();
            return $this->render('rss', $data, '');
        } elseif ($this->page == 'sitemap.xml') {

            // SITEMAP
            return $this->sitemap();
            
        } elseif ($this->page == 'buynow') {
          
          $cookiesites  = $this->config->get('site.cookiesites');
          $redirect_url = $this->config->get('site.url');
          if (is_array($cookiesites)) {
            shuffle($cookiesites);
            $redirect_url = $cookiesites[array_rand($cookiesites)];
          }
          header("Location: $redirect_url");
          exit;
          
        } else {

            // Check template pages
            $path_check = DIR_TMPL . "/pages/" . $this->page . ".phtml";
            if (file_exists($path_check)) {
                return $this->render('pages/' . $this->page, $data);
            } else {
                // 404
                return $this->error('404', $data);
            }

        }
    }

    /**
     * Sitemap.xml
     */
    private function sitemap()
    {
        header('Content-Type: text/xml');
        $docs   = '';
        $posts  = $this->posts->getList(300);
        foreach ($posts as $post) {
            $docs   .= $this->template->render('sitemap/sitemap_doc', array(
                'doc' => $post
            ), '') . "\n";
        }
        $sitemap = $this->template->render('sitemap/sitemap', array(
                'docs' => $docs
        ), '');
        $this->cache->setPage($this->page, $sitemap);
        echo $sitemap;
    }

    /**
     * Error: 404d
     */
    private function error($errorType, $data)
    {
        // add error header
        $httpCode   = '';
        switch ($errorType) {
            case '404':
                $httpCode = '404 Not Found';
                break;
            case '500':
                $httpCode = '500 Internal Server Error';
                break;
        }

        // Log errors in prod
        if ($this->config->get('site.env') == 'prod') {
            $remote_ip      = $_SERVER['REMOTE_ADDR'];
            $request_url    = $_SERVER['REDIRECT_URL'];
            $date           = date('Ymd h:i:s');
            file_put_contents(ROOT . '/error.log', "$date\t$remote_ip\t$request_url\n", FILE_APPEND | LOCK_EX);
        }

        header("HTTP/1.0 $httpCode");
        $data['page']['url']    = $this->config->get('site.url')."/error$errorType";
        $data['error']          = $errorType;

        // first check for cached 404 page
        if ($this->cache->renderPage($errorType)) {
            return;
        }
        // else get and cache 404 page
        $content                = $this->template->render($errorType, $data);
        $this->cache->setPage($errorType, $content);
        echo $content;
    }

    /**
     * Handle checking cache and sending off to templates for content
     */
    private function render($template, $data = null, $base = Template::BASE_TEMPLATE)
    {
        $content    = $this->template->render($template, $data, $base);
        $this->cache->setPage($this->page, $content);
        echo $content;
    }

    /**
     * Render appropriate mime header
     */
    private function renderHeader()
    {
        preg_match('/\.[^\/]+$/', $this->page, $matches);
        $ext    = '.html';
        if (count($matches)) {
            $ext    = $matches[0];
        }
        $mime   = 'text/plain';
        switch ($ext) {
            case '.xml': $mime = 'text/xml'; break;
            case '.txt': $mime = 'text/plain'; break;
            case '.rss': $mime = 'application/rss+xml; charset=ISO-8859-1'; break;
            case '.json': $mime = 'application/json'; break;
            case '.js': $mime = 'application/javascript'; break;
            case '.css': $mime = 'text/css'; break;
            default: $mime = 'text/html';
        }
        header("Content-Type: $mime");
    }
}
