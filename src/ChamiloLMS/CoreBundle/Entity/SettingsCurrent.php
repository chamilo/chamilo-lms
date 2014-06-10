<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SettingsCurrent
 *
 * @ORM\Table(name="settings_current", uniqueConstraints={@ORM\UniqueConstraint(name="unique_setting", columns={"variable", "subkey", "category", "access_url"})}, indexes={@ORM\Index(name="access_url", columns={"access_url"}), @ORM\Index(name="idx_settings_current_au_cat", columns={"access_url", "category"})})
 * @ORM\Entity
 */
class SettingsCurrent
{
    /**
     * @var string
     *
     * @ORM\Column(name="variable", type="string", length=255, nullable=true)
     */
    private $variable;

    /**
     * @var string
     *
     * @ORM\Column(name="subkey", type="string", length=255, nullable=true)
     */
    private $subkey;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=255, nullable=true)
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="selected_value", type="string", length=255, nullable=true)
     */
    private $selectedValue;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=255, nullable=true)
     */
    private $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="scope", type="string", length=50, nullable=true)
     */
    private $scope;

    /**
     * @var string
     *
     * @ORM\Column(name="subkeytext", type="string", length=255, nullable=true)
     */
    private $subkeytext;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_url", type="integer", nullable=false)
     */
    private $accessUrl;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_url_changeable", type="integer", nullable=false)
     */
    private $accessUrlChangeable;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_url_locked", type="integer", nullable=false)
     */
    private $accessUrlLocked;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
