<?php

class crawler {

    private $_url;
    private $_host;
    private $_maxPages;
    private $_wordCount;
    private $_pageload;
    private $_seen = array();
    private $_filter = array();
    private $_internalLink = array();
    private $_externalLink = array();
    private $_images = array();
    private $_pageTitles = array();

    public function __construct($url, $maxPages = 5)
    {
        $this->_url = $url;
        $this->_maxPages = $maxPages;
        $parse = parse_url($url);
        $this->_host = $parse['host'];
    }

    private function _processAnchors($content, $url)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        $anchors = $dom->getElementsByTagName('a');
        $images = $dom->getElementsByTagName('img');
        
        $list = $dom->getElementsByTagName("title");
        if ($list->length > 0) {
            array_push($this->_pageTitles, $list->item(0)->textContent);
        }

        foreach ($images as $img) {
            $imgSource = $img->getAttribute('src');

            array_push($this->_images, $imgSource);
        }

        foreach ($anchors as $element) {
            $href = $element->getAttribute('href');

            $path = '' . ltrim($href, '');

            $parts = parse_url($url);
            $href = $parts['scheme'] . '://';
            $href .= $parts['host'];
            $href .= $path;
            
            $linkhost = $parts['host'];

            // Check if this is an internal or external link
            if( $this->_host == $linkhost){
                array_push($this->_internalLink, $href);
            } else {
                array_push($this->_externalLink, $href);
            }

            $this->crawl_page($href);
        }
    }

    private function _getContent($url)
    {
        $handle = curl_init();

        $options = array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_MAXREDIRS      => 10,
        );

        curl_setopt_array( $handle, $options );

        // Get the HTML or whatever is linked in $url.
        $response = curl_exec($handle);
        
        // response total time
        $time = curl_getinfo($handle, CURLINFO_TOTAL_TIME);

        // get http status code
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        curl_close($handle);

        $this->_pageload += $time;

        return array($response, $httpCode, $time);
    }

    private function _printResult($url, $httpcode, $time)
    {
        if (ob_get_contents()){
            ob_end_flush();
        }
        
        $count = count($this->_seen);

        echo "<tr><td>$count</td><td>$httpcode</td><td>$time</td><td>$url</td></tr>";

        ob_start();
        flush();
    }

    private function isValid($url)
    {
        if (strpos($url, $this->_host) === false || count($this->_seen) === $this->_maxPages || isset($this->_seen[$url])
        ) {
            return false;
        }
        foreach ($this->_filter as $excludePath) {
            if (strpos($url, $excludePath) !== false) {
                return false;
            }
        }
        return true;
    }

    private function getAvg($set, $total) {

        foreach($set as $item){
            $sum += strlen($item);
        }

        return $sum / $total;

    }

    public function crawl_page($url)
    {

        if (!$this->isValid($url)) {
            return;
        }
         
        //add to the seen URL
        $this->_seen[$url] = true;
        
        //get Content and Return Code
        list($content, $httpcode, $time) = $this->_getContent($url);

        // Get rid of style, script, head
        $search = array('@<script[^>]*?>.*?</script>@si',
            '@<head>.*?</head>@siU',
            '@<style[^>]*?>.*?</style>@siU',
            '@<![\s\S]*?--[ \t\n\r]*>@'
        );

        $page_contents = file_get_contents($url);

        $page_contents = preg_replace($search, '', $page_contents); 

        $result = array_count_values( str_word_count( strip_tags($page_contents), 1 ) );

        foreach($result as $item){

            $this->_wordCount += $item;

        }
        
        // print Result for current Page
        $this->_printResult($url, $httpcode, $time);
        
        //process subPages
        $this->_processAnchors($content, $url);
    }

    public function run()
    {
        echo "<table>
            <tr>
                <th>Page</th>
                <th>HTTP Status Code</th>
                <th>Time</th>
                <th>URL</th>
            </tr>";

        $this->crawl_page($this->_url);

        $avgTitleLength = $this->getAvg($this->_pageTitles, $this->_maxPages);
        $avgWordCount = $this->_wordCount / $this->_maxPages;
        $avgPageload = $this->_pageload / $this->_maxPages;

        echo "</table>";
        
        echo "<p>";
        echo "Number of unique internal links: " . count(array_unique($this->_internalLink)) . "<br>";
        echo "Number of unique external links: " . count(array_unique($this->_externalLink)) . "<br>";
        echo "Number of unique images: " . count(array_unique($this->_images)) . "<br>";
        echo "Average Title length: " . $avgTitleLength . "<br>";
        echo "Average Word Count: " . $avgWordCount . "<br>";
        echo "Average Page Load: " . $avgPageload . "<br>";
        echo "</p>";
    }
}

?>