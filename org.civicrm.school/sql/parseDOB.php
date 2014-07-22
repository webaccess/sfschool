<?php

function getContactID( $grade, $last, $first ) {
    $grade = trim( $grade );
    $first = trim( $first );
    $last  = trim( $last  );

        $query = "
SELECT     c.id
FROM       civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE      s.grade_sis = %1
AND        c.first_name = %2
AND        c.last_name  = %3
";
        $params = array( 1 => array( $grade, 'Integer' ),
                         2 => array( $first, 'String'  ),
                         3 => array( $last , 'String'  ),
                         );
        return CRM_Core_DAO::singleValueQuery( $query, $params );
}

function readDOBFile($readFile, $writeFile) {
    $fdRead  = fopen( $readFile, "r" );
    if ( ! $fdRead ) {
        echo "Could not read input file: $readFile\n";
        exit( );
    }

    $fdWrite  = fopen( $writeFile, "w" );
    if ( ! $fdWrite ) {
        echo "Could not write output file: $writeFile\n";
        exit( );
    }

    // read first line
    $header = fgetcsv( $fdRead );

    $buffer = "";
    while ( $fields = fgetcsv( $fdRead ) ) {

        // get contact id
        $contactID = getContactID( $fields[1], $fields[3], $fields[2] );
        if ( ! $contactID ) {
            echo "Could not retrieve valid Contact ID for: {$fields[1]}, {$fields[3]}, {$fields[2]}\n";
            continue;
        }

        $buffer .= "UPDATE civicrm_contact SET birth_date = '{$fields[4]}' where id = $contactID;\n";
    }

    fputs( $fdWrite, $buffer );

    fclose( $fdRead  );
    fclose( $fdWrite );
}

function initialize( ) {
    require_once '/Users/lobo/public_html/drupal7/sites/school/civicrm.settings.php';

    require_once 'CRM/Core/Config.php';
    $config =& CRM_Core_Config::singleton( );

    require_once 'CRM/Core/Error.php';
}

function run( ) {
    initialize( );

    readDOBFile(
      '/Users/lobo/svn/school/org.civicrm.school/sql/DOB_PS.2012.csv',
      '/Users/lobo/svn/school/org.civicrm.school/sql/DOB_PS.2012.sql'
    );
}

run( );


