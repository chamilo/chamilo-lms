<?php
/**
 * This script defines as closely as possible the matches between
 * original tables and destination (chamilo) tables and fields.
 */
// This is an array that matches objects (for Chamilo)
$matches = array(
  /* We need a numerical index for each table for easier reference later.
     The same origin table can be defined several time if it has fields that
     go into different destination tables */
  0 => array (
    /* Original table */
    'orig_table' => 'Alumno',
    /* Destination table. Leave empty if you just want to scan the
       original table to build up migration data.
     */
    'dest_table' => '',
    /* Function to insert in dest table */
    'dest_func' => 'MigrationCustom::store_user_data1',
    /* List of fields */
    'fields_match' => array(
      /* Each field has a numerical ID for later processing */
      0 => array(
        /* Original field name */
        'orig' => 'intIdAlumno',
        /* If the SQL select has to alter the data before getting it in PHP,
           define the method that will return the right SQL select part
         */
        'sql_alter' => 'sql_alter_unhash_50',
        /* Destination field name - leave empty 
           and manage in func for complex operations */
        'dest' => 'user_id',
        /* The special method that will be called from 
           migration.custom.class.php when dealing with this particular field.
           Use 'none' if the field can be transferred "as is"
           If the original ID will not be re-used as is but will be required
           for the relationships with other tables, you should define a method
           that will log the relationship in a specific array.
        */
        'func' => 'none',
      ),
      1 => array(
        'orig' => 'chrUsuarioCreacion',
        'dest' => 'creation_date',
        'func' => 'none',
      ),
    )
  )
);
