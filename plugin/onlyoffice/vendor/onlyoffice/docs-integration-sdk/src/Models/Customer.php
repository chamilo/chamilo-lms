<?php

namespace Onlyoffice\DocsIntegrationSdk\Models;

/**
 *
 * (c) Copyright Ascensio System SIA 2024
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

class Customer extends JsonSerializable
{
    protected $address;
    protected $info;
    protected $logo;
    protected $logoDark;
    protected $mail;
    protected $name;
    protected $phone;
    protected $www;

    public function __construct(
        string $address = "",
        string $info = "",
        string $logo = "",
        string $logoDark = "",
        string $mail = "",
        string $name = "",
        string $phone = "",
        string $www = ""
    ) {
        $this->address = $address;
        $this->info = $info;
        $this->logo = $logo;
        $this->logoDark = $logoDark;
        $this->mail = $mail;
        $this->name = $name;
        $this->phone = $phone;
        $this->www = $www;
    }

    /**
     * Get the value of address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set the value of address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Get the value of info
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set the value of info
     */
    public function setInfo($info)
    {
        $this->info = $info;
    }

    /**
     * Get the value of logo
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set the value of logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    /**
     * Get the value of logoDark
     */
    public function getLogoDark()
    {
        return $this->logoDark;
    }

    /**
     * Set the value of logoDark
     */
    public function setLogoDark($logoDark)
    {
        $this->logoDark = $logoDark;
    }

    /**
     * Get the value of mail
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Set the value of mail
     */
    public function setMail($mail)
    {
        $this->mail = $mail;
    }

    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the value of phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set the value of phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * Get the value of www
     */
    public function getWww()
    {
        return $this->www;
    }

    /**
     * Set the value of www
     */
    public function setWww($www)
    {
        $this->www = $www;
    }
}
