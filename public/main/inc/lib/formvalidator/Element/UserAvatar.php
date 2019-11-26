<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;

/**
 * Class UserAvatar
 * FormValidator element to add an user avatar wrapping a hidden input with its user ID
 * Is necessary set an instance of Chamilo\UserBundle\Entity\User as value. The exported value is the user ID.
 */
class UserAvatar extends HTML_QuickForm_input
{
    /** @var User */
    private $user = null;
    private $imageSize = 'small';
    private $subTitle = '';

    /**
     * UserAvatar constructor.
     *
     * @param string $name
     * @param string $label
     * @param array  $attributes
     */
    public function __construct($name, $label, $attributes = [])
    {
        if (isset($attributes['image_size'])) {
            $this->imageSize = $attributes['image_size'];
            unset($attributes['image_size']);
        }

        if (isset($attributes['sub_title'])) {
            $this->subTitle = $attributes['sub_title'];
            unset($attributes['sub_title']);
        }

        parent::__construct($name, $label, $attributes);

        $this->setType('hidden');
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->user = !is_a($value, 'Chamilo\UserBundle\Entity\User')
            ? UserManager::getManager()->find($value)
            : $value;

        parent::setValue($this->user->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function toHtml()
    {
        if (!$this->user) {
            return '';
        }

        $userInfo = api_get_user_info($this->user->getId());
        $userPicture = isset($userInfo["avatar_{$this->imageSize}"])
            ? $userInfo["avatar_{$this->imageSize}"]
            : $userInfo["avatar"];

        if (!$this->subTitle) {
            $this->subTitle = $this->user->getUsername();
        }

        $html = parent::toHtml();
        $html .= '
            <div class="media">
                <div class="media-left">
                    <img src="'.$userPicture.'" alt="'.UserManager::formatUserFullName($this->user).'">
                </div>
                <div class="media-body">
                    <h4 class="media-heading">'.UserManager::formatUserFullName($this->user).'</h4>
                    '.$this->subTitle.'
                </div>
            </div>
        ';

        return $html;
    }
}
