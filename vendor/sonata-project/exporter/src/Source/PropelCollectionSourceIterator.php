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

namespace Sonata\Exporter\Source;

use PropelCollection;

/**
 * Read data from a PropelCollection.
 *
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
final class PropelCollectionSourceIterator extends AbstractPropertySourceIterator implements SourceIteratorInterface
{
    /**
     * @var \PropelCollection
     */
    private $collection;

    /**
     * @param array<string> $fields Fields to export
     */
    public function __construct(PropelCollection $collection, array $fields, string $dateTimeFormat = 'r')
    {
        $this->collection = clone $collection;

        parent::__construct($fields, $dateTimeFormat);
    }

    public function rewind(): void
    {
        if ($this->iterator) {
            $this->iterator->rewind();

            return;
        }

        $this->iterator = $this->collection->getIterator();
        $this->iterator->rewind();
    }
}
