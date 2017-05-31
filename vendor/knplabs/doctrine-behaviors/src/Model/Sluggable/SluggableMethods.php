<?php
/**
 * @author Lusitanian
 * Freely released with no restrictions, re-license however you'd like!
 */

namespace Knp\DoctrineBehaviors\Model\Sluggable;

/**
 * Sluggable trait.
 *
 * Should be used inside entities for which slugs should automatically be generated on creation for SEO/permalinks.
 */
trait SluggableMethods
{
    /**
     * Returns an array of the fields used to generate the slug.
     *
     * @abstract
     * @return array
     */
    abstract public function getSluggableFields();

    /**
     * Returns the slug's delimiter
     *
     * @return string
     */
    private function getSlugDelimiter()
    {
        return '-';
    }

    /**
     * Returns whether or not the slug gets regenerated on update.
     *
     * @return bool
     */
    private function getRegenerateSlugOnUpdate()
    {
        return true;
    }

    /**
     * Sets the entity's slug.
     *
     * @param $slug
     * @return $this
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }
    
    /**
     * Returns the entity's slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param $values
     * @return mixed|string
     */
    private function generateSlugValue($values)
    {
        $usableValues = [];
        foreach ($values as $fieldName => $fieldValue) {
            if (!empty($fieldValue)) {
                $usableValues[] = $fieldValue;
            }
        }

        if (count($usableValues) < 1) {
            throw new \UnexpectedValueException(
                'Sluggable expects to have at least one usable (non-empty) field from the following: [ ' . implode(array_keys($values), ',') .' ]'
            );
        }

        // generate the slug itself
        $sluggableText = implode(' ', $usableValues);

        $transliterator = new Transliterator;
        $sluggableText = $transliterator->transliterate($sluggableText, $this->getSlugDelimiter());

        $urlized = strtolower( trim( preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $sluggableText ), $this->getSlugDelimiter() ) );
        $urlized = preg_replace("/[\/_|+ -]+/", $this->getSlugDelimiter(), $urlized);

        return $urlized;
    }

    /**
     * Generates and sets the entity's slug. Called prePersist and preUpdate
     */
    public function generateSlug()
    {
        if ( $this->getRegenerateSlugOnUpdate() || empty( $this->slug ) ) {
            $fields = $this->getSluggableFields();
            $values = [];

            foreach ($fields as $field) {
                if (property_exists($this, $field)) {
                    $val = $this->{$field};
                } else {
                    $methodName = 'get' . ucfirst($field);
                    if (method_exists($this, $methodName)) {
                        $val = $this->{$methodName}();
                    } else {
                        $val = null;
                    }
                }

                $values[] = $val;
            }

            $this->slug = $this->generateSlugValue($values);
        }
    }
}
