<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Repository\Doctrine\Mapping;

use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Verb as VerbModel;

/**
 * A {@link Verb} mapped to a storage backend.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class Verb
{
    public $identifier;

    /**
     * @var string
     */
    public $id;

    /**
     * @var array|null
     */
    public $display;

    public function getModel()
    {
        $display = null;

        if (null !== $this->display) {
            $display = LanguageMap::create($this->display);
        }

        return new VerbModel(IRI::fromString($this->id), $display);
    }

    public static function fromModel(VerbModel $model)
    {
        $verb = new self();
        $verb->id = $model->getId()->getValue();

        if (null !== $display = $model->getDisplay()) {
            $verb->display = array();

            foreach ($display->languageTags() as $languageTag) {
                $verb->display[$languageTag] = $display[$languageTag];
            }
        }

        return $verb;
    }
}
