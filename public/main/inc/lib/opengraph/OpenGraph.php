<?php
/*
  Copyright 2010 Scott MacVicar

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

*/

class OpenGraph implements Iterator
{
  /**
   * There are base schema's based on type, this is just
   * a map so that the schema can be obtained
   *
   */
    public static $TYPES = array(
        'activity' => array('activity', 'sport'),
        'business' => array('bar', 'company', 'cafe', 'hotel', 'restaurant'),
        'group' => array('cause', 'sports_league', 'sports_team'),
        'organization' => array('band', 'government', 'non_profit', 'school', 'university'),
        'person' => array('actor', 'athlete', 'author', 'director', 'musician', 'politician', 'public_figure'),
        'place' => array('city', 'country', 'landmark', 'state_province'),
        'product' => array('album', 'book', 'drink', 'food', 'game', 'movie', 'product', 'song', 'tv_show'),
        'website' => array('blog', 'website'),
    );

  /**
   * Holds all the Open Graph values we've parsed from a page
   *
   */
    private $_values = array();

  /**
   * Fetches a URI and parses it for Open Graph data, returns
   * false on error.
   *
   * @param $URI    URI to page to parse for Open Graph data
   * @return OpenGraph
   */
    static public function fetch($URI) {
        $curl = curl_init($URI);

        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

        $response = curl_exec($curl);

        curl_close($curl);

        if (!empty($response)) {
            return self::_parse($response);
        } else {
            return false;
        }
    }

  /**
   * Parses HTML and extracts Open Graph data, this assumes
   * the document is at least well formed.
   *
   * @param $HTML    HTML to parse
   * @return OpenGraph
   */
    static private function _parse($HTML) {
        $old_libxml_error = libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->loadHTML($HTML);

        libxml_use_internal_errors($old_libxml_error);

        $tags = $doc->getElementsByTagName('meta');
        if (!$tags || $tags->length === 0) {
            return false;
        }

        $page = new self();

        $nonOgDescription = null;

        foreach ($tags AS $tag) {
            if ($tag->hasAttribute('property') &&
                strpos($tag->getAttribute('property'), 'og:') === 0) {
                $key = strtr(substr($tag->getAttribute('property'), 3), '-', '_');
                $page->_values[$key] = $tag->getAttribute('content');
            }

            //Added this if loop to retrieve description values from sites like the New York Times who have malformed it.
            if ($tag ->hasAttribute('value') && $tag->hasAttribute('property') &&
                strpos($tag->getAttribute('property'), 'og:') === 0) {
                $key = strtr(substr($tag->getAttribute('property'), 3), '-', '_');
                $page->_values[$key] = $tag->getAttribute('value');
            }
            if ($tag->hasAttribute('name') && $tag->getAttribute('name') === 'description') {
                $nonOgDescription = $tag->getAttribute('content');
            }

        }
        if (!isset($page->_values['title'])) {
            $titles = $doc->getElementsByTagName('title');
            if ($titles->length > 0) {
                $page->_values['title'] = $titles->item(0)->textContent;
            }
        }
        if (!isset($page->_values['description']) && $nonOgDescription) {
            $page->_values['description'] = $nonOgDescription;
        }

        //Fallback to use image_src if ogp::image isn't set.
        if (!isset($page->values['image'])) {
            $domxpath = new DOMXPath($doc);
            $elements = $domxpath->query("//link[@rel='image_src']");

            if ($elements->length > 0) {
                $domattr = $elements->item(0)->attributes->getNamedItem('href');
                if ($domattr) {
                    $page->_values['image'] = $domattr->value;
                    $page->_values['image_src'] = $domattr->value;
                }
            }
        }

        if (empty($page->_values)) { return false; }

        return $page;
    }

  /**
   * Helper method to access attributes directly
   * Example:
   * $graph->title
   *
   * @param $key    Key to fetch from the lookup
   */
    public function __get($key) {
        if (array_key_exists($key, $this->_values)) {
            return $this->_values[$key];
        }

        if ($key === 'schema') {
            foreach (self::$TYPES AS $schema => $types) {
                if (array_search($this->_values['type'], $types)) {
                    return $schema;
                }
            }
        }
    }

  /**
   * Return all the keys found on the page
   *
   * @return array
   */
    public function keys() {
        return array_keys($this->_values);
    }

  /**
   * Helper method to check an attribute exists
   *
   * @param $key
   */
    public function __isset($key) {
        return array_key_exists($key, $this->_values);
    }

  /**
   * Will return true if the page has location data embedded
   *
   * @return boolean Check if the page has location data
   */
    public function hasLocation() {
        if (array_key_exists('latitude', $this->_values) && array_key_exists('longitude', $this->_values)) {
            return true;
        }

        $address_keys = array('street_address', 'locality', 'region', 'postal_code', 'country_name');
        $valid_address = true;
        foreach ($address_keys AS $key) {
            $valid_address = ($valid_address && array_key_exists($key, $this->_values));
        }
        return $valid_address;
    }

  /**
   * Iterator code
   */
    private $_position = 0;
    public function rewind() { reset($this->_values); $this->_position = 0; }
    public function current() { return current($this->_values); }
    public function key() { return key($this->_values); }
    public function next() { next($this->_values); ++$this->_position; }
    public function valid() { return $this->_position < sizeof($this->_values); }
}
