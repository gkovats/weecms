<?php

// Can't load directly
if (!defined('ROOT')) {
    exit();
}

    // Define import directories
define('DIR_POSTS', ROOT . '/posts');

/**
 * Posts class
 */
class Posts
{

    // static instance
    private static $instance;

    // config
    private $config;

    // Constructor
    private function __construct()
    {
        $this->config = Config::getInstance();
    }

    /**
     * Fetch instance of this class
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Posts();
        }

        return self::$instance;
    }

    /**
     * Fetch a list of available post files
     */
    public function getList($count = 900000, $starting = 0, $ascending = false)
    {
        $root       = str_replace('\\', '/', DIR_POSTS);
        $postFiles  = glob("$root/*.txt");
        $posts      = array();
        if ($ascending) {
            sort($postFiles);
        } else {
            rsort($postFiles);
        }
        if ($starting > count($postFiles)) {
            return array();
        }
        $postFiles = array_splice($postFiles, $starting, $count);

        foreach ($postFiles as $i => $postFile) {
            $post_id    = str_replace(array(
                "$root/",
                ".txt"
            ), '', $postFile);
            $post = $this->getPost($post_id);
            if (is_array($post)) {
                $posts[] = $post;
            }
        }
        return $posts;
    }

    /**
     * Fetch a list of available post files matching a given list
     */
    public function getTagList($tag, $count, $starting = 0, $ascending = false)
    {
        $posts  = $this->getList();
        $list   = array();
        foreach ($posts as $post) {
            if (in_array($tag, $post['tags'])) {
                $list[] = $post;
            }
        }
        $posts = array_splice($list, $starting, $count);
        return $posts;
    }

    /**
     * Given a post id, get the post
     */
    public function getPost($post_id)
    {
        $post_id    = preg_replace('/[^a-z0-9\.\-\_]+/i', '', trim($post_id));
        $Parsedown  = new Parsedown();
        $post       = array(
            'id'    => $post_id,
            'url'   => $this->config->get('site.url')."/$post_id",
            'tags'  => array(),
            'pic'   => '',
            'title' => $post_id
        );
        $file       = DIR_POSTS . "/$post_id.txt";
        if (!file_exists($file)) {
            return false;
        }
        $text       = file_get_contents($file);

        // Parse out the crazy smart characters
        $text       = $this->cleanText($text);

        // get title
        preg_match('/^#+(.*)$/m', $text, $matches);
        if (is_array($matches) && count($matches)) {
            $post['title'] = trim($matches[1]);
            $text = trim(preg_replace('/^#.*$/m', '', $text, 1));
        }

        // get pic
        preg_match('/\\[PIC\s*([^\\]]+)\\]/', $text, $matches);
        if (is_array($matches) && count($matches) > 1) {
            $post['img']   = "/pics/".$matches[1];
            $text          = str_replace($matches[0], '', $text);
        }

        // get tags
        preg_match_all('/\\[TAG\s*([^\\]]+)\\]/', $text, $matches);
        if (is_array($matches) && count($matches) > 1) {
            foreach ($matches[1] as $i=>$tag) {
                $post['tags'][] = $tag;
                $text           = str_replace($matches[0][$i], '', $text);
            }
        }

        // get summary
        $post['summary_text'] = str_replace('<!--more-->', '[MORE]', $text);
        $point = strpos($text, '[MORE]');

        if ($point === false) {
            if (strlen($text) > 200) {
                $point = strpos($text, "\n", 200);
            } else {
                $point = strlen($text);
            }
        } else {
            $text = str_replace('[MORE]', '', $text);
        }
        $post['summary_text'] = substr($text, 0, $point);



        $post['summary'] = $Parsedown->text($post['summary_text']);

        // get content
        $post['content_text'] = $text;
        $post['content'] = $Parsedown->text($text);

        // get date
        $post['date'] = filemtime($file);
        preg_match('/(\d\d\d\d)(\d\d)(\d\d)\./', $post_id, $matches);

        if (is_array($matches) && count($matches) > 3) {
            $post['date'] = strtotime($matches[2] . '/' . $matches[3] . '/' . $matches[1] . ' 12:00pm');
        }

        // get image

        return $post;
    }

    /**
     * Clean text of smart characters
     */
    public function cleanText($text)
    {
        $chr_map = array(
            // Windows codepage 1252
            "\xC2\x82" => "'", // U+0082?U+201A single low-9 quotation mark
            "\xC2\x84" => '"', // U+0084?U+201E double low-9 quotation mark
            "\xC2\x8B" => "'", // U+008B?U+2039 single left-pointing angle quotation mark
            "\xC2\x91" => "'", // U+0091?U+2018 left single quotation mark
            "\xC2\x92" => "'", // U+0092?U+2019 right single quotation mark
            "\xC2\x93" => '"', // U+0093?U+201C left double quotation mark
            "\xC2\x94" => '"', // U+0094?U+201D right double quotation mark
            "\xC2\x9B" => "'", // U+009B?U+203A single right-pointing angle quotation mark

            // Regular Unicode     // U+0022 quotation mark (")
            // U+0027 apostrophe     (')
            "\xC2\xAB"     => '"', // U+00AB left-pointing double angle quotation mark
            "\xC2\xBB"     => '"', // U+00BB right-pointing double angle quotation mark
            "\xE2\x80\x98" => "'", // U+2018 left single quotation mark
            "\xE2\x80\x99" => "'", // U+2019 right single quotation mark
            "\xE2\x80\x9A" => "'", // U+201A single low-9 quotation mark
            "\xE2\x80\x9B" => "'", // U+201B single high-reversed-9 quotation mark
            "\xE2\x80\x9C" => '"', // U+201C left double quotation mark
            "\xE2\x80\x9D" => '"', // U+201D right double quotation mark
            "\xE2\x80\x9E" => '"', // U+201E double low-9 quotation mark
            "\xE2\x80\x9F" => '"', // U+201F double high-reversed-9 quotation mark
            "\xE2\x80\xB9" => "'", // U+2039 single left-pointing angle quotation mark
            "\xE2\x80\xBA" => "'", // U+203A single right-pointing angle quotation mark
        );
        $chr    = array_keys($chr_map); // but: for efficiency you should
        $rpl    = array_values($chr_map); // pre-calculate these two arrays
        $text   = str_replace($chr, $rpl, html_entity_decode($text, ENT_QUOTES, "UTF-8"));
        return $text;
    }
}
