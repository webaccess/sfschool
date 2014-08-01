<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 2.2                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2009                                |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007.                                       |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License along with this program; if not, contact CiviCRM LLC       |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2009
 * $Id$
 *
 */

class School_Utils_CloseActivity {

  function closeActivity( $days ) {
    $condition = "AND v.name = 'Completed'";
    $getStatus = CRM_Core_OptionGroup::values( 'activity_status', TRUE, FALSE, FALSE, $condition );
    $getActivityTypeId = CRM_Core_OptionGroup::values( 'activity_type', TRUE, FALSE, FALSE );

    $sql = "
SELECT id
FROM civicrm_activity
WHERE DATE_FORMAT(activity_date_time,'%Y-%m-%d') = CURDATE() + INTERVAL %1 DAY AND activity_type_id = %2 ";

    $params = array(
      1 => array( $days, 'Integer' ) ,
      2 => array( $getActivityTypeId['Parent Teacher Conference'], 'Integer' )
    );

    $dao = CRM_Core_DAO::executeQuery( $sql, $params );
    if ( $dao->N >= 1 ) {
      $activity_id = array( );
      while ( $dao->fetch( ) ) {
        $activity_id[] = $dao->id;
      }
      $id = implode( ",", $activity_id );
      $update_query = "
UPDATE civicrm_activity
SET status_id = %1
WHERE id IN (%2)";

      $update_params = array(
        1 => array( $getStatus['Completed'], 'Integer'),
        2 => array( $id, 'String')
      );

      $update_dao = CRM_Core_DAO::executeQuery( $update_query, $update_params );
    }
  }
}

