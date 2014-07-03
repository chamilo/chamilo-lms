<?php
namespace Mopa\Bundle\BootstrapBundle\Menu\Converter;

use Mopa\Bundle\BootstrapBundle\Menu\Factory\MenuExtension;
use Knp\Menu\ItemInterface;

/**
 * Converts some menu to fit css classes for the Navbar to be displayed nicely
 *
 * Currently the menu is not changed, displaying a multi level menu with e.g. list group might lead to unexpected results
 *
 * Either we implement a flattening option or warn, or ignore this as its done now
 *
 * @author phiamo <phiamo@googlemail.com>
 *
 */
class MenuConverter
{
    protected $decorator;

    protected $possibleNavs = array("navbar", "pills", "list-group");

    /**
     * Build extension
     */
    public function __construct()
    {
        $this->decorator = new MenuExtension();
    }

    /**
     * Convert an Menu to be a bootstrap menu
     *
     * The options array expect a key "automenu" set to a string of possibleNavs
     *
     * Additional options may be specified an code tightened
     *
     * @param \Knp\Menu\ItemInterface $item
     * @param array                   $options
     */
    public function convert(ItemInterface $item, array $options)
    {
        $autoRootOptions = $this->getRootOptions($options);

        $rootOptions = $this->decorator->buildOptions($autoRootOptions);

        $this->decorator->buildItem($item, $rootOptions);

        $this->convertCildren($item, $options);
    }

    /**
     * Convert Menu children to be a bootstrap menu
     *
     * The options array expect a key "automenu" set to a string of possibleNavs
     *
     * Additional options may be specified an code tightened
     *
     * @param \Knp\Menu\ItemInterface $item
     * @param array                   $options
     *
     * @return null
     */

    public function convertCildren(ItemInterface $item, array $options)
    {
        foreach ($item->getChildren() as $sitem) {

            $autoChildOptions = $this->getChildOptions($sitem, $options);

            $childOptions = $this->decorator->buildOptions($autoChildOptions);

            $this->decorator->buildItem($sitem, $childOptions);

            if (isset($option['autochilds']) && $option['autochilds']) {
                $this->convertCildren($sitem, $options);
            }
        }
    }

    /**
     * Sets options for the Root element given to convert
     *
     * @param array $options
     *
     * @throws \RuntimeException
     * @return array             $options
     */
    protected function getRootOptions(array $options)
    {
        if (!in_array($options["automenu"], $this->possibleNavs)) {
            throw new \RuntimeException("Value 'automenu' is '".$options["automenu"]."' not one of ".implode("', '", $this->possibleNavs));
        }

        return array_merge($options, array(
            $options["automenu"] => true // navbar, pills etc => true
        ));
    }

    /**
     * Setting guessed good values for different menu / nav types
     *
     * @param ItemInterface $item
     * @param array         $options
     *
     * @return array $options
     */
    protected function getChildOptions(ItemInterface $item, array $options)
    {
        $childOptions = array();

        if (in_array($options["automenu"], array("navbar")) && $item->hasChildren()) {
            $childOptions = array(
                "dropdown" => !isset($options["dropdown"]) || $options["dropdown"],
                "caret" => !isset($options["caret"]) || $options["caret"],
            );
        }

        if (in_array($options["automenu"], array("list-group"))) {
            $childOptions = array(
                "list-group-item" => true,
            );
        }

        return array_merge($options, $childOptions);
    }

}
