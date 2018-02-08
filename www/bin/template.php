<?php

// Can't load directly
if (!defined('ROOT')) {
    exit;
}

/**
 * Template class
 */
class Template
{
    const BASE_TEMPLATE = 'base/site';

    // Current page
    public static $page;

    // Config
    private $config;

    // Posts
    private $posts;

    // Data
    private $data = array();

    // Constructor
    public function __construct()
    {
        $this->config   = Config::getInstance();
        $this->posts    = Posts::getInstance();
    }

    /**
     * Add an array to the current set of template data
     * 
     * @param array $data Array to add to current template data
     */
    public function updateData(array $data)
    {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Update a field in template data via .notation
     * 
     * @param string $field Name of template data field to update
     * @param mixed  $value Value to set
     */
    public function setData(string $field, $value)
    {
        $keys       = explode('.', $field);
        $pointer    = &$this->data;
        while (count($keys) && is_array($pointer)) {
            $key = array_shift($keys);
            if (!isset($pointer[$key])) {
                $pointer[$key] = array();
            }
            $pointer = &$pointer[$key];
        }
        $pointer = $value;
    }

    /**
     * Fetch from data by .notation
     * 
     * @param  string $field   Name of template data field to retrieve
     * @param  string $default Value to return if field isn't found
     * @return string          Value found, or the default
     */
    public function getData(string $field, string $default = '')
    {
        $keys   = explode('.', $field);
        $data   = $this->data;
        while (count($keys) && is_array($data)) {
            $key = array_shift($keys);
            if (!isset($data[$key])) {
                return $default;
            }
            $data   = $data[$key];
        }
        return $data;
    }

    /**
     * Pull in a partial template
     */
    public function partial($template)
    {
        if (empty($template)) {
            return '';
        }
        if (!file_exists(DIR_TMPL . "/$template.phtml")) {
            return "<!-- Partial: couldn't find $template partial -->";
        }
        // @GK: switch to include / php parse
        ob_start();
        include(DIR_TMPL . "/$template.phtml");
        $content    = ob_get_contents();
        ob_end_clean();
        return trim($content);
    }

    /**
     * Render page template for output
     *
     * @param  string  $template Content template to use
     * @param  array   $data     Data to use in template
     * @param  string  $base     Base page template to use
     * @param  boolean $return   [optional] If true, will return string output, otherwise render to stdout
     * @return string|null
     */
    public function render(string $template, array $data = array(), string $base = self::BASE_TEMPLATE, $return = true)
    {
        $this->setData('page.content', 'test');
        
        $this->updateData($data);
        $content = $this->parseTemplate($template);

        if (!empty($base)) {
            $this->setData('page.content', $content);
            $content = $this->parseTemplate($base);
        }
        
        if ($return) {
            return $content;
        } else {
            echo $content;
        }
    }

    /**
     * Processes a content string for template keywords
     *
     * @param  string $template Name of template to fetch
     * @return string Content parsed for keywords
     */
    private function parseTemplate(string $template)
    {
        $loop       = 0;
        $partials   = array();

        if (!file_exists(DIR_TMPL . "/$template.phtml")) {
            echo "Template $template not found.";
            exit();
        }

        ob_start();
        include(DIR_TMPL . "/$template.phtml");
        $content    = ob_get_contents();
        ob_end_clean();

        // PARTIAL keyword
        $keywords = $this->getKeywords($content, 'PARTIAL');
        while ($keywords && $loop < 5) {
            foreach ($keywords as $keyword) {
                $partial = $keyword ['value'];
                $partialContent = '';
                if (isset($partials [$partial])) {
                    $partialContent = $partials [$partial];
                } else {
                    $partialContent = $this->partial($partial);
                    $partials [$partial] = $partialContent;
                }
                // finally string replace
                $content = str_replace($keyword ['string'], $partialContent, $content);
            }

            $loop ++;
            $keywords = $this->getKeywords($content, 'PARTIAL');
        }

        // DATE keyword
        $keywords = $this->getKeywords($content, 'DATE');
        if ($keywords) {
            foreach ($keywords as $keyword) {
                $parts = explode(' ', $keyword ['value']);
                $format = $parts [0];
                $date = time();
                if (count($parts) > 1) {
                    $date = $parts [1];
                    if (!intval($date)) {
                        $date = $this->getData($date);
                    } else {
                        $date = intval($date);
                    }
                    if (!$date) {
                        $date = time();
                    }
                }
                $content = str_replace($keyword ['string'], date($format, $date), $content);
            }
        }

        // DATA keyword
        // Allows a page to set data values
        $keywords = $this->getKeywords($content, 'DATA');
        if ($keywords) {
            foreach ($keywords as $keyword) {
                // No content alteration, so just remove
                $content = str_replace($keyword ['string'], '', $content);
                // fetch config detail
                $values = explode('=', $keyword['value']);
                if (!is_array($values) || count($values) < 2) {
                    break;
                }
                $this->setData($values[0], $values[1]);
            }
        }

        // ECHO keyword
        $keywords = $this->getKeywords($content, 'ECHO');
        if ($keywords) {
            foreach ($keywords as $keyword) {
                $value  = $this->getData($keyword ['value']);
                if (!is_string($value)) {
                    $value = '';
                }
                $content = str_replace($keyword ['string'], $value, $content);
            }
        }

        // CONFIG keyword
        // write out a config value
        $keywords = $this->getKeywords($content, 'CONFIG');
        if ($keywords) {
            foreach ($keywords as $keyword) {
                $content = str_replace($keyword['string'], $this->config->get($keyword ['value'], ''), $content);
            }
        }

        // META keyword
        // Reserved for Page title and other standard page items
        $keywords = $this->getKeywords($content, 'META');
        if ($keywords) {
            foreach ($keywords as $keyword) {
                $data = false;
                $args   = explode(' ', $keyword['value']);
                $term   = strtolower($args[0]);

                switch ($term) {

                    // page title
                    case 'title':
                        $data = $this->config->get('site.title');
                        $page_title = $this->getData('page.title');
                        if ($page_title) {
                            $data = "$page_title | $data";
                        }
                        break;

                    // page description
                    case 'description':
                        $data = $this->config->get('site.description');
                        $page_desc = $this->getData('page.description');
                        if ($page_desc) {
                            $data = $page_desc;
                        }
                        break;

                    // page url
                    case 'url':
                        $data       = $this->config->get('site.url') . '/';
                        $page_url   = $this->getData('page.url');
                        if ($page_url) {
                            $data = $page_url;
                        }
                        break;

                    // Post links list
                    case 'postlinks':
                        $count  = 10;
                        if (count($args) > 1) {
                            $count = intval($args[1]);
                        }
                        $posts  = $this->posts->getList($count);
                        foreach ($posts as $post) {
                            $data .= '<li><a href="'.$post['url'].'">'.$post['title'].'</a></li>'."\n";
                        }
                        break;

                    // Links list
                    case 'links':
                        $links = $this->config->get('links');
                        foreach ($links as $title => $link) {
                            $data .= '<li><a href="' . $link . '" target="_blank">' . $title . '</a></li>'."\n";
                        }
                        break;

                }
                if (!$data || !is_string($data)) {
                    $data = '';
                }

                $content = str_replace($keyword ['string'], $data, $content);
            }
        }
        
        // CONTENT keyword for base templates
        $content = str_replace('[CONTENT]', $this->getData('page.content'), $content);
        return $content;
    }

    /**
     * Fetch template operators
     * 
     * @param  string $content Content / haystack to search through
     * @param  string $keyword Template keyword to look for
     * @return array  List of locations and keyword values found
     */
    private function getKeywords(string $content, string $keyword)
    {
        $keywords = array();
        
        if (strpos($content, "[$keyword") === false) {
            return $keywords;
        }
        preg_match_all('/\[' . $keyword . '\s*([^\]]*)\]/', $content, $matches);
        if (!is_array($matches) || !count($matches [0])) {
            return $keywords;
        }

        for ($i = 0; $i < count($matches [0]); $i ++) {
            $keywords [] = array(
                'value'     => $matches [1] [$i],
                'string'    => $matches [0] [$i]
            );
        }
        return $keywords;
    }
}
