<?php

use Doctrine\ORM\QueryBuilder;

/**
 * Description of repository
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class EntityRepository extends Doctrine\ORM\EntityRepository
{

    function next_id($entity = null)
    {
        $column = $this->get_id_field();
        if (empty($column)) {
            return false;
        }

        $metadata = $this->getClassMetadata();
        $entity_name = $metadata->name;

        if ($this->is_course_table()) {
            $course = $entity ? $entity->get_c_id() : Entity::current_course();
            $course = $course ? $course : Entity::current_course();
            $c_id = is_object($course) ? $course->get_id() : $course;
            if (empty($c_id)) {
                return null;
            }

            $query = new QueryBuilder($this->getEntityManager());
            $query = $query->select("MAX(t.id) AS m")->from($entity_name, 't')->where('t.c_id = ' . $c_id);
        } else {
            $query = new QueryBuilder($this->getEntityManager());
            $query = $query->select("MAX(t.$column) AS m")->from($entity_name, 't');
        }
        $result = $this->getEntityManager()->createQuery($query);
        $result = $result->getSingleScalarResult();
        $result += 10; //in case somebody does an insert in between
        return $result;
    }

    /**
     *
     * @return string|bool
     */
    function get_id_field()
    {
        $metadata = $this->getClassMetadata();
        if (count($metadata->identifier) == 1) {
            $field = $metadata->identifier[0];
            return $field;
        }
        if (count($metadata->identifier) > 2) {
            return false;
        }

        if (isset($metadata->identifier['id'])) {
            return 'id';
        }

        if (!$this->is_course_table()) {
            return false;
        }


        foreach ($metadata->identifier as $field) {
            if ($field != 'c_id' && $field != 'course') {
                return $field;
            }
        }
        return false;
    }

    function is_course_table()
    {
        $metadata = $this->getClassMetadata();
        $table = $metadata->table['name'];
        return strpos($table, 'c_') === 0;
    }

}