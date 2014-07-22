<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
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

define( 'School_BALANCE_OVERDUE', 10 );
require_once 'Utils.php';

function run( ) {
    School_bin_Utils_auth( FALSE );

    $config =& CRM_Core_Config::singleton( );

    require_once '../school.php';
    school_civicrm_config( $config );

    require_once 'School/Utils/ExtendedCare.php';
    $details = School_Utils_ExtendedCare::balanceDetails( null, '20120901', '20130831' );

    $values = array( );
    $globalID = 2324;
    foreach ( $details as $contactID => $detail ) {
        if ( $detail['balanceCredit'] > 10 ) {
            $values[] = "{$detail['name']},$globalID,$contactID,Credit,Carry Over Credit from 2012-2013 Academic year,2013-09-01,{$detail['balanceCredit']},Carry Over Blocks";
            $globalID++;
        }

        if ( $detail['balanceDue'] > 10 ) {
            $values[] = "{$detail['name']},$globalID,$contactID,Charge,Carry Over Balance Due from 2012-2013 Academic year,2013-09-01,{$detail['balanceDue']},Carry Over Blocks";
            $globalID++;
        }
    }

    CRM_Core_Error::debug( implode( "\n", $values ) );
}

run( );
