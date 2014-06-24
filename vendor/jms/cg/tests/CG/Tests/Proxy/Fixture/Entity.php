<?php

namespace CG\Tests\Proxy\Fixture;

class Entity
{
    public function getName()
    {
        return 'foo';
    }

    public final function getBaz()
    {
    }

    protected function getFoo()
    {
    }

    private function getBar()
    {
    }
}