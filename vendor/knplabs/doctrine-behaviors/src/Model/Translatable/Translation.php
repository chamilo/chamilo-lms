<?php

/*
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\Model\Translatable;

/**
 * Translation trait.
 *
 * Should be used inside translation entity.
 */
trait Translation
{
    use TranslationProperties,
        TranslationMethods
    ;
}
