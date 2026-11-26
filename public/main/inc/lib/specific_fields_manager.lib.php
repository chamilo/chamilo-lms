<?php

use Chamilo\CoreBundle\Entity\SearchEngineField;
use Chamilo\CoreBundle\Framework\Container;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * Manage specific tools.
 *
 * @todo convert into a class
 */

// Database table definitions
$table_sf = Database::get_main_table(TABLE_MAIN_SPECIFIC_FIELD);
$table_sf_val = Database::get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);

/**
 * Add a specific field.
 *
 * @param string $name specific field name
 *
 * @return int|false
 */
function add_specific_field($name)
{
    $name = trim($name);
    if ('' === $name) {
        return false;
    }

    $code = get_specific_field_code_from_name($name);
    if (false === $code) {
        return false;
    }

    // The unique constraint also protects against concurrent code allocation.
    $connection = Database::getConnection();
    try {
        $connection->insert('search_engine_field', ['code' => $code, 'title' => $name]);
    } catch (UniqueConstraintViolationException) {
        return false;
    }

    return (int) $connection->lastInsertId();
}

/**
 * Delete a specific field.
 *
 * @param int $id specific field id
 */
function delete_specific_field($id)
{
    $id = (int) $id;
    if (!is_numeric($id)) {
        return false;
    }
    Container::getEntityManager()->createQueryBuilder()
        ->delete(SearchEngineField::class, 'sf')
        ->where('sf.id = :id')
        ->setParameter('id', $id)
        ->getQuery()
        ->execute();
    //TODO also delete the corresponding values
}

/**
 * Edit a specific field.
 *
 * @param int    $id   specific field id
 * @param string $name new field name
 *
 * @return bool
 */
function edit_specific_field($id, $name)
{
    $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $name = trim($name);
    if (false === $id || '' === $name) {
        return false;
    }

    $connection = Database::getConnection();
    if (false === $connection->fetchOne('SELECT id FROM search_engine_field WHERE id = ?', [$id])) {
        return false;
    }

    // Renaming a field must not change its existing search prefix or values.
    $connection->update('search_engine_field', ['title' => $name], ['id' => $id]);

    return true;
}

/**
 * @param array $conditions a list of condition (exemple : status=>STUDENT)
 * @param array $order_by   a list of fields on which to sort
 *
 * @return array An array with all specific fields, at platform level
 */
function get_specific_field_list($conditions = [], $order_by = [])
{
    $qb = Database::getConnection()->createQueryBuilder()
        ->select('id', 'code', 'title')
        ->from('search_engine_field');
    $columns = ['id' => 'id', 'code' => 'code', 'title' => 'title', 'name' => 'title'];

    foreach ($conditions as $field => $value) {
        if (!isset($columns[$field]) || !is_scalar($value)) {
            return [];
        }

        // Older callers pass quoted code literals. Treat them as values, not SQL.
        if (is_string($value) && str_starts_with($value, "'") && str_ends_with($value, "'")) {
            $value = substr($value, 1, -1);
        }
        $qb->andWhere($columns[$field].' = '.$qb->createNamedParameter($value));
    }

    foreach ($order_by as $order) {
        if (!is_string($order) || !preg_match('/^(id|code|title|name)(?:\s+(ASC|DESC))?$/i', trim($order), $matches)) {
            continue;
        }
        $qb->addOrderBy($columns[strtolower($matches[1])], strtoupper($matches[2] ?? 'ASC'));
    }

    $rows = [];
    foreach ($qb->executeQuery()->fetchAllAssociative() as $row) {
        // Preserve the existing three numeric table columns for legacy callers.
        $row[] = $row['id'];
        $row[] = $row['code'];
        $row[] = $row['title'];
        $rows[] = $row;
    }

    return $rows;
}

/**
 * @param array $conditions a list of condition (exemple : status=>STUDENT)
 * @param array $order_by   a list of fields on which sort
 *
 * @return array an array with all users of the platform
 */
function get_specific_field_values_list(
    $conditions = [],
    $order_by = []
) {
    $table_sfv = Database::get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);
    $return_array = [];
    $sql = "SELECT * FROM $table_sfv";
    if (count($conditions) > 0) {
        $sql .= ' WHERE ';

        //Fixing course id
        if (isset($conditions['c_id'])) {
            $course_info = api_get_course_info_by_id(intval($conditions['c_id']));
            $conditions['course_code'] = " '".$course_info['code']."' ";
            unset($conditions['c_id']);
        }
        //If any course_code is provided try to insert the current course code
        if (!isset($conditions['course_code'])) {
            $conditions['course_code'] = " '".api_get_course_id()."' ";
        }

        $conditions_string_array = [];
        foreach ($conditions as $field => $value) {
            $conditions_string_array[] = $field.' = '.$value;
        }
        $sql .= implode(' AND ', $conditions_string_array);
    }
    if (count($order_by) > 0) {
        $sql .= ' ORDER BY '.implode(',', $order_by);
    }
    // TODO(db-neutral): broken today (table search_engine_field_values does not exist; it is search_engine_field_value), left raw
    $sql_result = Database::query($sql);
    while ($result = Database::fetch_array($sql_result)) {
        $return_array[] = $result;
    }

    return $return_array;
}

/**
 * @param string $prefix      xapian prefix
 * @param string $course_code
 * @param string $tool_id     Constant from mainapi.lib.php
 * @param int    $ref_id      representative id inside one tool item
 *
 * @return array
 */
function get_specific_field_values_list_by_prefix(
    $prefix,
    $course_code,
    $tool_id,
    $ref_id
) {
    $table_sf = Database::get_main_table(TABLE_MAIN_SPECIFIC_FIELD);
    $table_sfv = Database::get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);
    $sql = 'SELECT sfv.value FROM %s sf LEFT JOIN %s sfv ON sf.id = sfv.field_id'.
        ' WHERE sf.code = \'%s\' AND sfv.c_id = \'%s\' AND tool_id = \'%s\' AND sfv.ref_id = %s';
    $sql = sprintf(
        $sql,
        $table_sf,
        $table_sfv,
        $prefix,
        $course_code,
        $tool_id,
        $ref_id
    );
    // TODO(db-neutral): broken today (table search_engine_field_values does not exist; it is search_engine_field_value), left raw
    $sql_result = Database::query($sql);
    while ($result = Database::fetch_array($sql_result)) {
        $return_array[] = $result;
    }

    return $return_array;
}

/**
 * Add a specific field value.
 *
 * @param int    $id_specific_field specific field id
 * @param string $course_id         course code
 * @param string $tool_id           tool id, from main.api.lib
 * @param int    $ref_id            intern id inside specific tool table
 * @param string $value             specific field value
 */
function add_specific_field_value(
    $id_specific_field,
    $course_id,
    $tool_id,
    $ref_id,
    $value
) {
    $table_sf_values = Database::get_main_table(
        TABLE_MAIN_SPECIFIC_FIELD_VALUES
    );
    $value = trim($value);
    if (empty($value)) {
        return false;
    }
    $sql = 'INSERT INTO %s(id, course_code, tool_id, ref_id, field_id, value) VALUES(NULL, \'%s\', \'%s\', %s, %s, \'%s\')';
    $sql = sprintf(
        $sql,
        $table_sf_values,
        $course_id,
        $tool_id,
        $ref_id,
        $id_specific_field,
        Database::escape_string($value)
    );
    // TODO(db-neutral): broken today (table search_engine_field_values does not exist; it is search_engine_field_value), left raw
    $result = Database::query($sql);
    if ($result) {
        return Database::insert_id();
    } else {
        return false;
    }
}

/**
 * Delete all values from a specific field id, course_id, ref_id and tool.
 *
 * @param string $course_id         course code
 * @param int    $id_specific_field specific field id
 * @param string $tool_id           tool id, from main.api.lib
 * @param int    $ref_id            intern id inside specific tool table
 */
function delete_all_specific_field_value($course_id, $id_specific_field, $tool_id, $ref_id)
{
    $table_sf_values = Database::get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);
    $sql = 'DELETE FROM %s WHERE course_code = \'%s\' AND tool_id = \'%s\' AND ref_id = %s AND field_id = %s';
    $sql = sprintf($sql, $table_sf_values, $course_id, $tool_id, $ref_id, $id_specific_field);
    // TODO(db-neutral): broken today (table search_engine_field_values does not exist; it is search_engine_field_value), left raw
    Database::query($sql);
}

/**
 * Delete all values from a specific item (course_id, tool_id and ref_id).
 * To be used when deleting such item from Dokeos.
 *
 * @param   string  Course code
 * @param   string  Tool ID
 * @param   int     Internal ID used in specific tool table
 */
function delete_all_values_for_item($course_id, $tool_id, $ref_id)
{
    $table_sf_values = Database::get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);
    $sql = 'DELETE FROM %s WHERE course_code = \'%s\' AND tool_id = \'%s\' AND ref_id = %s';
    $sql = sprintf($sql, $table_sf_values, $course_id, $tool_id, $ref_id);
    // TODO(db-neutral): broken today (table search_engine_field_values does not exist; it is search_engine_field_value), left raw
    Database::query($sql);
}

/**
 * Generates a code (one-letter string) for a given field name
 * Defaults to the first letter of the name, otherwise iterate through available
 * letters.
 *
 * @param   string  Name
 *
 * @return string|false One-letter code, upper-case, or false when no code is available
 */
function get_specific_field_code_from_name($name)
{
    // Preserve the legacy reserved letters and compare case-insensitively.
    $allowed = str_split('ABDEFGHIJKLMNPQRSTUVWXY');
    $used = array_map('strtoupper', Database::getConnection()->fetchFirstColumn('SELECT code FROM search_engine_field'));
    $preferred = strtoupper(substr(trim($name), 0, 1));
    if (in_array($preferred, $allowed, true) && !in_array($preferred, $used, true)) {
        return $preferred;
    }

    foreach ($allowed as $code) {
        if (!in_array($code, $used, true)) {
            return $code;
        }
    }

    return false;
}
