<?php

/* For licensing terms, see /license.txt */

/**
 * SessionSliderBlockWidget class
 * Get the embed url and thumbnail from an youtube video url
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class SessionsSliderBlockWidget
{

    /**
     * Get the Youtube video ID from its URL
     * @param string $url The URL video
     * @return string The id if exists. Otherwise return false
     */
    private function getYoutubeVideoId($url)
    {
        $query = parse_url($url, PHP_URL_QUERY);

        $queryParts = explode('&', $query);

        foreach ($queryParts as $param) {
            $paramInfo = explode('=', $param);

            if (strtolower($paramInfo[0]) === 'v') {
                return $paramInfo[1];
            }
        }

        return false;
    }

    /**
     * Get the URL to embed for a Youtube video from its video URL
     * @param string $url The URL video
     * @return string The id if exists. Otherwise return false
     */
    public function getUrlToEmbed($url)
    {
        $videoId = $this->getYoutubeVideoId($url);

        if ($videoId === false) {
            return false;
        }

        return "https://www.youtube.com/embed/$videoId";
    }

    /**
     * Get the HQ image default for a Youtube video from its video URL
     * @param string $url The URL video
     * @return string The image URL if video ID exists. Otherwise return false
     */
    public function getVideoThumbnail($url)
    {
        $videoId = $this->getYoutubeVideoId($url);

        if ($videoId === false) {
            return false;
        }

        return "https://img.youtube.com/vi/$videoId/hqdefault.jpg";
    }

}
