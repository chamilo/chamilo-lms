<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;

/**
 * Gedmo\Translatable\Entity\Translation.
 *
 * @ORM\Table(
 *         name="ext_translations",
 *         indexes={@ORM\index(name="translations_lookup_idx", columns={
 *             "locale", "object_class", "foreign_key"
 *         })},
 *         uniqueConstraints={@UniqueConstraint(name="lookup_unique_idx", columns={
 *             "locale", "object_class", "field", "foreign_key"
 *         })}
 * )
 * @ORM\Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 *
 * @ORM\AttributeOverrides({
 *     @ORM\AttributeOverride(name="objectClass",
 *         column=@ORM\Column(
 *             name="object_class",
 *             type="string",
 *             length=190
 *         )
 *     )
 * })
 */
class Translation extends AbstractTranslation
{
}
