<?php

namespace Knp\Bundle\MarkdownBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Knp\Bundle\MarkdownBundle\DependencyInjection\Compiler\ParsersCompilerPass;

class KnpMarkdownBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ParsersCompilerPass());
    }
}
