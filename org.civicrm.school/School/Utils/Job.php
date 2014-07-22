<?php
  /*
    +--------------------------------------------------------------------+
    | CiviCRM version 4.2                                                |
    +--------------------------------------------------------------------+
    | Copyright CiviCRM LLC (c) 2004-2012                                |
    +--------------------------------------------------------------------+
    | This file is a part of CiviCRM.                                    |
    |                                                                    |
    | CiviCRM is free software; you can copy, modify, and distribute it  |
    | under the terms of the GNU Affero General Public License           |
    | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
    |                                                                    |
    | CiviCRM is distributed in the hope that it will be useful, but     |
    | WITHOUT ANY WARRANTY; without even the implied warranty of         |
    | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
    | See the GNU Affero General Public License for more details.        |
    |                                                                    |
    | You should have received a copy of the GNU Affero General Public   |
    | License and the CiviCRM Licensing Exception along                  |
    | with this program; if not, contact CiviCRM LLC                     |
    | at info[AT]civicrm[DOT]org. If you have questions about the        |
    | GNU Affero General Public License or the licensing of CiviCRM,     |
    | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
    +--------------------------------------------------------------------+
  */

  /**
   *
   * @package CRM
   * @copyright CiviCRM LLC (c) 2004-2012
   * $Id$
   *
   */

  /**
   * This class contains definition of jobs related to CiviSchool
   *
   */
class School_Utils_job {

    /*
     * @access public
     *
     * @return None
     */

    function checkParentLogin( ) {

        $config = CRM_Core_Config::singleton();
        $db_uf = DB::parseDSN($config->userFrameworkDSN);
        
        // first cache all the contacts who have created a login
        $sql = "
SELECT c.id
FROM   civicrm_contact c,
       civicrm_uf_match uf,
       {$db_uf['database']}.users u
WHERE  uf.contact_id = c.id
AND    uf.uf_id = u.uid
AND    u.created != u.access
";
    
        $dao = CRM_Core_DAO::executeQuery( $sql );
    
        $accountsCreted = array( );
        while ( $dao->fetch( ) ) {
            $accountsCreated[$dao->id] = 1;
        }

        // now fetch all the student parent information
        $sql = '
SELECT      c.id as c_id, c.display_name as c_name, s.grade_sis as c_grade, p.id as p_id, p.display_name as p_name, ep.email as p_email
FROM        civicrm_contact c
INNER JOIN  civicrm_value_school_information s ON s.entity_id = c.id
INNER JOIN  civicrm_relationship r ON r.contact_id_a = c.id
INNER JOIN  civicrm_contact p      ON r.contact_id_b = p.id
LEFT  JOIN  civicrm_email   ep     ON ep.contact_id  = p.id
WHERE c.contact_sub_type = "' . CRM_Core_DAO::VALUE_SEPARATOR . 'Student' . CRM_Core_DAO::VALUE_SEPARATOR . 
            '" AND   s.grade_sis >= 1
AND   r.relationship_type_id = 1
ORDER BY p_id';
    
  
        $parentsDoNotHaveLogin = array( );
        $parentsDoHaveLogin    = array( );

        $dao = CRM_Core_DAO::executeQuery( $sql );
        while ( $dao->fetch( ) ) {
            if ( array_key_exists( $dao->p_id, $accountsCreated ) ) {
                unset( $parentsDoNotHaveLogin[$dao->c_id] );
                if ( ! array_key_exists( $dao->c_id, $parentsDoHaveLogin ) ) {
                    $parentsDoHaveLogin[$dao->c_id] = array( );
                }
                $parentsDoHaveLogin[$dao->c_id][] = array( $dao->c_name, $dao->c_grade, $dao->p_id, $dao->p_name, $dao->p_email );
            } else if ( array_key_exists( $dao->c_id,  $parentsDoHaveLogin ) ) {
                unset( $parentsDoNotHaveLogin[$dao->c_id] );
                $parentsDoHaveLogin[$dao->c_id][] = array( $dao->c_name, $dao->c_grade, $dao->p_id, $dao->p_name, $dao->p_email );
            } else {
                if ( ! array_key_exists( $dao->c_id, $parentsDoNotHaveLogin ) ) {
                    $parentsDoNotHaveLogin[$dao->c_id] = array( );
                }
                $parentsDoNotHaveLogin[$dao->c_id][] = array( $dao->c_name, $dao->c_grade, $dao->p_id, $dao->p_name, $dao->p_email );
            }
        }

        $families = array( );
        $emailAddress = array( );
        foreach ( $parentsDoNotHaveLogin as $cid => $pValues ) {
            $familyKey = $familyValue = array( );
            foreach ( $pValues as $pValue ) {
                $familyKey[]   = $pValue[2];
                if ( ! empty( $pValue[4] ) ) {
                    $familyValue[]  = "{$pValue[3]} <{$pValue[4]}>";
                    $emailAddress[$pValue[4]] = "{$pValue[3]} <{$pValue[4]}>";
                } else {
                    $familyValue[]  = $pValue[3];
                }
            }
            $families[implode('_', $familyKey )] = implode( ', ', $familyValue );
        }

        CRM_Core_Error::debug( count( $emailAddress ), implode( ', ', $emailAddress ) );
        CRM_Core_Error::debug( count( $families ), $families );

        $familiesLoggedIn = array( );
        foreach ( $parentsDoHaveLogin as $cid => $pValues ) {
            $familyKey = $familyValue = array( );
            foreach ( $pValues as $pValue ) {
                $familyKey[]   = $pValue[2];
                $familyValue[] = "{$pValue[3]} <{$pValue[4]}>";
            }
            $familiesLoggedIn[implode('_', $familyKey )] = implode( ', ', $familyValue );
        }

        CRM_Core_Error::debug( count( $familiesLoggedIn ), $familiesLoggedIn );
    }


    function yearlyExport( $params ) {
        require_once 'School/Utils/ExtendedCareFees.php';
        require_once 'School/Utils/ExtendedCare.php';
        // modified timestamps for ease of use
        $startTimestamp = strtotime($params['start_date']); 
        $endTimestamp = strtotime($params['end_date']); 
        $startDate =  date('Ymd', $startTimestamp);
        $endDate   = date('Ymd', $endTimestamp);
        $semesterYear = $params['sem_year'];

        $config = CRM_Core_Config::singleton( );
        CRM_Utils_File::createDir( $config->configAndLogDir . DIRECTORY_SEPARATOR . $semesterYear );

        $sql = '
SELECT c.id, c.sort_name, v.grade_sis
FROM   civicrm_contact c
INNER JOIN civicrm_value_school_information v ON c.id = v.entity_id
WHERE c.contact_sub_type = "' . CRM_Core_DAO::VALUE_SEPARATOR . ' Student ' . CRM_Core_DAO::VALUE_SEPARATOR . 
            '"AND   v.grade_sis > -2
AND   v.grade_sis < 10
ORDER BY v.grade_sis, c.id'
            ;

        $dao = CRM_Core_DAO::executeQuery( $sql );
        while ( $dao->fetch( ) ) {
            $id = $dao->id;
            $studentName = $dao->sort_name;
            echo "$id, $studentName, {$dao->grade_sis}<p>";
            flush( );

            $feeDetails = School_Utils_ExtendedCareFees::feeDetails( $startDate,
                                                                  $endDate,
                                                                  null,
                                                                  false,
                                                                  true,
                                                                  $id,
                                                                  null );
    
            $allDetails = array( );
            $allDetails['Payments and Charges'] = array_pop( $feeDetails );

            $ecMonth = School_Utils_ExtendedCare::signoutDetailsPerMonth( $startDate, $endDate, $id );
            if ( ! empty( $ecMonth ) ) {
                $allDetails['Extended care charges per month'] = 
                    array( 'details' => $ecMonth );
            }

            $dayCharges = School_Utils_ExtendedCare::signoutDetails( $startDate, $endDate, true, true, false, $id );
            if ( ! empty( $dayCharges ) ) {
                $allDetails['Extended care charges per day'] =  array_pop( $dayCharges );
            }

            if ( ! CRM_Utils_Array::crmIsEmptyArray( $allDetails ) ) {
                $fp = fopen( $config->configAndLogDir . DIRECTORY_SEPARATOR .
                             $seemsterYear . DIRECTORY_SEPARATOR .
                             "{$studentName}.csv", "w" );

                foreach ( $allDetails as $name => $fields ) {
                    $displayHeaders = false;
                    if ( ! empty( $fields['details'] ) ) {
                        $tempHeaders = array( 'Charge Type' );
                        $tempValues  = array( $name );
                        foreach ( $fields as $key => $value ) {
                            if ( $key == 'details' ) {
                                continue;
                            }
                            $tempHeaders[] = $key;
                            $tempValues[]  = $value;
                        }
                        if ( ! empty( $tempHeaders ) ) {
                            fputcsv( $fp, $tempHeaders );
                            fputcsv( $fp, $tempValues );
                            fputcsv( $fp, array( ) );
                        }

                        foreach ( $fields['details'] as $fID => $fValues ) {
                            if ( ! $displayHeaders ) {
                                $displayHeaders = true;
                                fputcsv( $fp, array_keys( $fValues ) );
                            }
                            fputcsv( $fp, array_values( $fValues ) );
                        }
                        fputcsv( $fp, array( ) );
                    }
                }
                fclose( $fp );
            }
        }
    }

    /** Helper functions  **/
    function _loadFiles( $inputDir, $year, $term, &$files ) {
   
        $dir =
            $inputDir . DIRECTORY_SEPARATOR .
            $year     . DIRECTORY_SEPARATOR .
            $term;
 
        $outDir =
            $inputDir     . DIRECTORY_SEPARATOR .
            'reportCards' . DIRECTORY_SEPARATOR .
            $year         . DIRECTORY_SEPARATOR .
            $term;
  
        CRM_Utils_File::createDir( $outDir );

        $grades = scandir( $dir );
        $files = array( );

        foreach ( $grades as $grade ) {
            if ( ! is_numeric( $grade ) ) {
                continue;
            }

            $gradeDir = $dir . DIRECTORY_SEPARATOR . $grade;
            $gradeFiles = scandir( $gradeDir );
            if ( ! empty( $gradeFiles ) ) {
                foreach ( $gradeFiles as $reportCard ) {
                    $fileInfo = pathInfo( $reportCard );
                    if ( ! is_numeric( $fileInfo['filename'] ) ||
                         $fileInfo['extension'] != 'pdf' ) {
                        continue;
                    }

                    $path    = $gradeDir . DIRECTORY_SEPARATOR . $reportCard;
                    $newGradeDir   = $outDir . DIRECTORY_SEPARATOR . $grade;
                    CRM_Utils_File::createDir( $newGradeDir );

                    list( $contactID, $firstName, $lastName, $gradeDB ) = self::_getContactInfo( $fileInfo['filename'] );
                    if ( empty( $contactID ) ) {
                        echo "Could not find matching student record for reportFile: $path\n";
                        continue;
                    }

                    $cleanFirst = CRM_Utils_String::munge( $firstName );
                    $cleanLast  = CRM_Utils_String::munge( $lastName  );
                    $newReportCard = "{$cleanFirst}_{$cleanLast}_{$fileInfo['filename']}_" . md5( uniqid( rand( ), true ) ) . ".pdf";
                    $newPath = $newGradeDir . DIRECTORY_SEPARATOR . $newReportCard;
                    if ( ! copy( $path, $newPath ) ) {
                        echo "Could not copy $path to $newPath\n";
                        continue;
                    }

                    // fix newPath so we remove the inputDir offset
                    $newPath = str_replace( $inputDir . DIRECTORY_SEPARATOR,
                                            '',
                                            $newPath );

                    $files[] = array( 'grade'         => $grade,
                                      'studentNumber' => $fileInfo['filename'],
                                      'fileName'      => $reportCard,
                                      'path'          => $path,
                                      'newPath'       => $newPath,
                                      'contactID'     => $contactID,
                                      'firstName'     => $firstName,
                                      'lastName'      => $lastName,
                                      'gradeDB'       => $gradeDB,
                                      'isValid'       => 1 );
                }
            }
        }
    }

    function _getContactInfo( $studentNumber ) {
        $sql = "
SELECT     c.id as contact_id, c.first_name, c.last_name, s.grade
FROM       civicrm_contact c
INNER JOIN civicrm_value_school_information s ON s.entity_id = c.id
WHERE      c.external_identifier = 'Student-$studentNumber'
AND        s.is_currently_enrolled = 1
";
        $dao = CRM_Core_DAO::executeQuery( $sql );
        if ( $dao->fetch( ) ) {
            return array( $dao->contact_id, $dao->first_name, $dao->last_name, $dao->grade );
        } else {
            return array( null, null, null, null );
        }
    }

    function _validateFiles( &$files, $year, $term ) {
        $sql = "
SELECT id
FROM   civicrm_value_report_cards
WHERE  entity_id = %1
AND    report_year = %2
AND    report_grade = %3
AND    report_term = %4
";
        $params = array( 1 => array( 0    , 'Integer' ),
                         2 => array( $year, 'String'  ),
                         3 => array( '0'  , 'String'  ),
                         4 => array( $term, 'String'  ) );

        foreach ( $files as $idx =>& $file ) {
            echo "Validating {$file['firstName']}, {$file['lastName']}, {$file['gradeDB']}\n";

            // check various params
            if ( empty( $file['contactID'] ) ) {
                echo "Could not find matching student record for reportFile: {$file['path']}\n";
                $file['isValid'] = 0;
                continue;
            }

            if ( $file['gradeDB'] != $file['grade'] ) {
                echo "Grades do not match for {$file['path']}, {$file['grade']}, DB Grade: {$file['gradeDB']}\n";
                $file['isValid'] = 0;
                continue;
            }

            // check for first name last name in file using mdfind
            $name = strtolower( "{$file['firstName']} {$file['lastName']}" );
            $fileName = exec( "/usr/bin/mdfind -onlyin {$file['path']} \"$name\"", $dontCare );
            if ( trim( $fileName ) != trim( $file['path'] ) ) {
                echo "Name: $name does not exist in file {$file['path']}\n";
                $file['isValid'] = 0;
                continue;
            }

            // check if this entry already exists, if so move on
            $params[1][0] = $file['contactID'];
            $params[3][0] = $file['grade'];
            if ( CRM_Core_DAO::singleValueQuery( $sql, $params ) ) {
                echo "Name: $name already has a report card {$file['path']} attached.\n";
                $file['isValid'] = 0;
                continue;
            }
        }
    }

    function _generateSQL( &$files, $year, $term,
                          $customValueCounter,
                          $entityFileCounter,
                          $fileCounter ) {
        $customValueSQL = $entityFileSQL = $fileSQL = array( );
        $now = date( 'Y-m-d h:i:s' );

        foreach ( $files as $idx =>& $file ) {
            if ( ! $file['isValid'] ) {
                continue;
            }

            $customValueSQL[] = "( $customValueCounter, {$file['contactID']}, '$year', '$term', '{$file['grade']}', $entityFileCounter )";
            $entityFileSQL[]  = "( $entityFileCounter, 'civicrm_value_report_cards_13', {$file['contactID']}, $fileCounter )";
            $fileSQL[]        = "( $fileCounter, 'application/pdf', '{$file['newPath']}', '$now' )";

            $customValueCounter++;
            $entityFileCounter++;
            $fileCounter++;
        }

        $sql  = null;
        $sql .= "
INSERT INTO civicrm_file ( id, mime_type, uri, upload_date )
VALUES
" . implode( ",\n", $fileSQL ) . ";\n";

        $sql .= "
INSERT INTO civicrm_entity_file ( id, entity_table, entity_id, file_id )
VALUES
" . implode( ",\n", $entityFileSQL ) . ";\n";

        $sql .= "
INSERT INTO civicrm_value_report_cards ( id, entity_id, report_year, report_term, report_grade, report_pdf_76 )
VALUES
" . implode( ",\n", $customValueSQL ) . ";\n";

        return $sql;
    }
    /** Helper functions  **/

    function addReport( $params ) {
        
        $files = array( );
        
        self::_loadFiles( $params['inputDir'], $params['year'], $params['term'], $files );
        
        self:: _validateFiles( $files, $params['year'], $params['term'] );
        
        // at this point all the files in validateFiles are valid, so now lets generate the sql
        $sql = self::_generateSQL( $files, $params['year'], $params['term'], 202, 202, 202 );
       
        CRM_Core_Error::debug( '$sql', $sql );
    }

    function genSISFile( $params ) {
        set_time_limit(0);
        require_once 'School/Utils/PowerSchool.php';
        $config =& CRM_Core_Config::singleton( );

        school_civicrm_config( $config );

        $time = null;
    
        // if first day of month, then generate monthly report
        if ( $params['all'] ) {
            $time = null;
        } else if ( date( 'j' ) == 1 || $params['month'] ) {
            $time = strftime( "%Y-%m-%d", time( ) - 31 * 24 * 60 * 60 );
        } else if ( date( 'N' ) == 1 || $params['week'] ) { // if monday, generate weekly report
            $time = strftime( "%Y-%m-%d", time( ) - 8 * 24 * 60 * 60 );
        } else { // generate daily report
            $time = strftime( "%Y-%m-%d", time( ) - 30 * 60 * 60 );
        }

        School_Utils_PowerSchool::export( $time, true );
    }

    function genYearlyBalance( $params ) {
        define( 'School_BALANCE_OVERDUE', 10 );
        require_once 'School/Utils/ExtendedCare.php';
        $config =& CRM_Core_Config::singleton( );
        school_civicrm_config( $config );
        $startTimestamp = strtotime($params['start_date']); 
        $endTimestamp = strtotime($params['end_date']); 
        $startDate =  date('Ymd', $startTimestamp);
        $endDate   = date('Ymd', $endTimestamp);
        $academicYear = $params['academic_year'];
        $details = School_Utils_ExtendedCare::balanceDetails( null, $startDate, $endDate );
    
        $values = array( );
        $globalID = $params['global_id'];
        foreach ( $details as $contactID => $detail ) {
            if ( $detail['balanceCredit'] > 10 ) {
                $values[] = "$globalID, $contactID, Credit, Carry Over Credit from $academicYear Academic year, $startDate, {$detail['balanceCredit']}, Carry Over Blocks";
                $globalID++;
            }

            if ( $detail['balanceDue'] > 10 ) {
                $values[] = "$globalID, $contactID, Charge, Carry Over Balance Due from $academicYear Academic year, $startDate, {$detail['balanceDue']}, Carry Over Blocks";
                $globalID++;
            }
        }

        CRM_Core_Error::debug( implode( "\n", $values ) );
    }    

  }

