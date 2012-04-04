<?php
// entities/Product.php
/**
 * @Entity @Table(name="products")
 **/
class User
{    
    protected $id;    
    public $username;
    
     public function getId()
    {
        return $this->id;
    }
    
    public function getUsername()
    {
        return $this->name;
    }

    public function setUsername($name)
    {
        $this->username = $name;
    }
}