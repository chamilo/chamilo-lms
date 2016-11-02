<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User as ChUser;

class InputUser extends HTML_QuickForm_input
{
    /** @var ChUser */
    private $user;
    private $imageSize;
    private $subTitle;

    public function __construct($name, $label, ChUser $user = null, $attributes = [])
    {
        $this->user = $user;
        $this->imageSize = 'small';
        $this->subTitle = null;

        if (isset($attributes['image_size'])) {
            $this->imageSize = $attributes['image_size'];
            unset($attributes['image_size']);
        }

        parent::__construct($name, $label, $attributes);

        $this->setType('hidden');

        if ($this->user) {
            $this->subTitle = $this->user->getUsername();
            $this->setValue($this->user->getId());
        }

        if (isset($attributes['sub_title'])) {
            $this->subTitle = $attributes['sub_title'];
            unset($attributes['sub_title']);
        }
    }

    public function toHtml()
    {
        $html = parent::toHtml();
        $html .= '
            <div class="media">
                <div class="media-left">
                    <img src="' . $this->user->getUserPicture($this->imageSize) . '" alt="' . $this->user->getCompleteName() . '">
                </div>
                <div class="media-body">
                    <h4 class="media-heading">' . $this->user->getCompleteName() . '</h4>
                    ' . $this->subTitle . '
                </div>
            </div>
        ';

        return $html;
    }
}