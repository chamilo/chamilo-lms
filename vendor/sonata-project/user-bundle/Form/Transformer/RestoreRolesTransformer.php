<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Sonata\UserBundle\Form\Transformer;

use Sonata\UserBundle\Security\EditableRolesBuilder;
use Symfony\Component\Form\DataTransformerInterface;

class RestoreRolesTransformer implements DataTransformerInterface
{
    protected $originalRoles = null;

    protected $rolesBuilder  = null;

    /**
     * @param EditableRolesBuilder $rolesBuilder
     */
    public function __construct(EditableRolesBuilder $rolesBuilder)
    {
        $this->rolesBuilder = $rolesBuilder;
    }

    /**
     * @param array $originalRoles
     */
    public function setOriginalRoles($originalRoles)
    {
        $this->originalRoles = $originalRoles;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value === null) {
            return $value;
        }

        if ($this->originalRoles === null) {
            throw new \RuntimeException('Invalid state, originalRoles array is not set');
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($selectedRoles)
    {
        if ($this->originalRoles === null) {
            throw new \RuntimeException('Invalid state, originalRoles array is not set');
        }

        list($availableRoles, ) = $this->rolesBuilder->getRoles();

        $hiddenRoles = array_diff($this->originalRoles, $availableRoles);

        return array_merge($selectedRoles, $hiddenRoles);
    }
}