<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Model;

interface UserInterface extends \FOS\UserBundle\Model\UserInterface
{
    public const GENDER_FEMALE = 'f';
    public const GENDER_MALE = 'm';
    public const GENDER_UNKNOWN = 'u';

    /**
     * Sets the creation date.
     *
     * @param \DateTime|null $createdAt
     *
     * @return UserInterface
     */
    public function setCreatedAt(\DateTime $createdAt = null);

    /**
     * Returns the creation date.
     *
     * @return \DateTime|null
     */
    public function getCreatedAt();

    /**
     * Sets the last update date.
     *
     * @param \DateTime|null $updatedAt
     *
     * @return UserInterface
     */
    public function setUpdatedAt(\DateTime $updatedAt = null);

    /**
     * Returns the last update date.
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt();

    /**
     * Sets the user groups.
     *
     * @param array $groups
     *
     * @return UserInterface
     */
    public function setGroups($groups);

    /**
     * Sets the two-step verification code.
     *
     * @param string $twoStepVerificationCode
     *
     * @return UserInterface
     */
    public function setTwoStepVerificationCode($twoStepVerificationCode);

    /**
     * Returns the two-step verification code.
     *
     * @return string
     */
    public function getTwoStepVerificationCode();

    /**
     * @param string $biography
     *
     * @return UserInterface
     */
    public function setBiography($biography);

    /**
     * @return string
     */
    public function getBiography();

    /**
     * @param \DateTime $dateOfBirth
     *
     * @return UserInterface
     */
    public function setDateOfBirth($dateOfBirth);

    /**
     * @return \DateTime
     */
    public function getDateOfBirth();

    /**
     * @param string $facebookData
     *
     * @return UserInterface
     */
    public function setFacebookData($facebookData);

    /**
     * @return string
     */
    public function getFacebookData();

    /**
     * @param string $facebookName
     *
     * @return UserInterface
     */
    public function setFacebookName($facebookName);

    /**
     * @return string
     */
    public function getFacebookName();

    /**
     * @param string $facebookUid
     *
     * @return UserInterface
     */
    public function setFacebookUid($facebookUid);

    /**
     * @return string
     */
    public function getFacebookUid();

    /**
     * @param string $firstname
     *
     * @return UserInterface
     */
    public function setFirstname($firstname);

    /**
     * @return string
     */
    public function getFirstname();

    /**
     * @param string $gender
     *
     * @return UserInterface
     */
    public function setGender($gender);

    /**
     * @return string
     */
    public function getGender();

    /**
     * @param string $gplusData
     *
     * @return UserInterface
     */
    public function setGplusData($gplusData);

    /**
     * @return string
     */
    public function getGplusData();

    /**
     * @param string $gplusName
     *
     * @return UserInterface
     */
    public function setGplusName($gplusName);

    /**
     * @return string
     */
    public function getGplusName();

    /**
     * @param string $gplusUid
     *
     * @return UserInterface
     */
    public function setGplusUid($gplusUid);

    /**
     * @return string
     */
    public function getGplusUid();

    /**
     * @param string $lastname
     *
     * @return UserInterface
     */
    public function setLastname($lastname);

    /**
     * @return string
     */
    public function getLastname();

    /**
     * @param string $locale
     *
     * @return UserInterface
     */
    public function setLocale($locale);

    /**
     * @return string
     */
    public function getLocale();

    /**
     * @param string $phone
     *
     * @return UserInterface
     */
    public function setPhone($phone);

    /**
     * @return string
     */
    public function getPhone();

    /**
     * @param string $timezone
     *
     * @return UserInterface
     */
    public function setTimezone($timezone);

    /**
     * @return string
     */
    public function getTimezone();

    /**
     * @param string $twitterData
     *
     * @return UserInterface
     */
    public function setTwitterData($twitterData);

    /**
     * @return string
     */
    public function getTwitterData();

    /**
     * @param string $twitterName
     *
     * @return UserInterface
     */
    public function setTwitterName($twitterName);

    /**
     * @return string
     */
    public function getTwitterName();

    /**
     * @param string $twitterUid
     *
     * @return UserInterface
     */
    public function setTwitterUid($twitterUid);

    /**
     * @return string
     */
    public function getTwitterUid();

    /**
     * @param string $website
     *
     * @return UserInterface
     */
    public function setWebsite($website);

    /**
     * @return string
     */
    public function getWebsite();

    /**
     * @param string $token
     *
     * @return UserInterface
     */
    public function setToken($token);

    /**
     * @return string
     */
    public function getToken();

    /**
     * @return string
     */
    public function getFullname();

    /**
     * @return array
     */
    public function getRealRoles();

    /**
     * @param array $roles
     *
     * @return UserInterface
     */
    public function setRealRoles(array $roles);
}
