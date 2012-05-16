<?php

/**
 * Process all renderers attached in order.
 * 
 * If a renderer returns an key value that has not already been set add it
 * to the result. Otherwise let the previous value unchanged.
 *
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */
class AssetAggregatedRenderer extends AssetRenderer
{

    protected $renderers = array();

    public function __construct($renderers)
    {
        $this->renderers = $renderers;
    }

    /**
     *
     * @return array
     */
    public function renderers()
    {
        return $this->renderers;
    }

    /**
     *
     * @param HttpResource $asset
     * @return array
     */
    public function render($asset)
    {
        $result = array();
        $plugins = self::plugins();
        foreach ($this->renderers as $renderer)
        {
            $data = $renderer->render($asset);
            $data = $data ? $data : array();
            foreach ($data as $key => $value)
            {
                if (!isset($result[$key]) && !empty($value))
                {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

}