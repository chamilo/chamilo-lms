<?php

class BBBPlugin extends Plugin
{    
    static function create() {
        static $result = null;
        return $result ? $result : $result = new self();
    }
    
    protected function __construct() {
        parent::__construct('2.0', 'Julio Montoya, Yannick Warnier');
    }
}