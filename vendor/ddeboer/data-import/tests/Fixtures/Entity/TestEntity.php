<?php

namespace Ddeboer\DataImport\Tests\Fixtures\Entity;

class TestEntity
{
    private $firstProperty;

    private $secondProperty;

    private $firstAssociation;

    public function getFirstProperty()
    {
        return $this->firstProperty;
    }

    public function setFirstProperty($firstProperty)
    {
        $this->firstProperty = $firstProperty;
    }

    public function getSecondProperty()
    {
        return $this->secondProperty;
    }

    public function setSecondProperty($secondProperty)
    {
        $this->secondProperty = $secondProperty;
    }

    public function getFirstAssociation() 
    {
        return $this->firstAssociation;
    }

    public function setFirstAssociation($firstAssociation)
    {
        $this->firstAssociation = $firstAssociation;
    }

}
