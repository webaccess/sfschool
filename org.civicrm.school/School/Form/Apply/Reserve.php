<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.2                                                |
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


require_once 'School/Form/Apply/Schedule.php';
require_once 'School/Form/Apply.php';
require_once 'School/Utils/Query.php';

class School_Form_Apply_Reserve extends CRM_Core_Form {

  protected $_target_id          = null;
  protected $_target_activity_id = null;        
  protected $_activity_date_time = null;
        
  function preProcess(){
    $this->_fromEmail = CRM_Core_BAO_Domain::getNameAndEmail();        
    $session            = CRM_Core_Session::singleton();
    $this->_parentId    = CRM_Utils_Request::retrieve( 'pid', 'Positive', $this, false, 
                                                       $session->get( 'userID'), 'REQUEST' );
    $this->_actTypeId   = CRM_Utils_Request::retrieve( 'atid', 'Integer', $this, true, null, 'REQUEST' );
    $this->_actType     = CRM_Core_OptionGroup::getLabel( 'activity_type', $this->_actTypeId );
       
    $isApplicantIdReqd = true;
    if( $this->_actType == 'Interview' ) {
      CRM_Utils_System::setTitle( ts('Schedule Parent Interview') );
    } elseif ( $this->_actType == 'Visit') {
      CRM_Utils_System::setTitle( ts('Schedule Child Visit') );
    } elseif ( $this->_actType == 'Tour' ) {
      CRM_Utils_System::setTitle( ts('Tour Booking') );
      $isApplicantIdReqd = false;
    }
    $this->_applicantId     = 
      CRM_Utils_Request::retrieve( 'cid', 'Integer', $this, $isApplicantIdReqd, null, 'REQUEST' );
    $this->_targetContactId = $this->_actType == 'Visit' ? $this->_applicantId : $this->_parentId;

    if ( $this->_applicantId && $this->_actType != 'Tour' ) {
      $isAppComplete = School_Form_Apply::checkApplicantStatus( $this->_applicantId, $this->_parentId );
      if ( $isAppComplete && School_Form_Apply::isPaymentRequired( $this->_applicantId ) ) {
        $payment = School_Form_Apply::getPaymentDetails( $this->_applicantId );
        if ( empty( $payment ) ) {
          CRM_Core_Error::statusBounce( ts('Application fee is to be paid before %1 could be scheduled. You may book a tour anytime.', array( 1 => $this->_actType ) ) );
        }
      } else if ( ! $isAppComplete ) {
        CRM_Core_Error::statusBounce( ts('Make sure application is complete. You may book a tour anytime.', array( 1 => $this->_actType ) ) );
      }
    }

    // check if there is any reserved slot
    $getActivityDetails        = School_Form_Apply::getActivityDetails( $this->_actTypeId , $this->_targetContactId );
    if( !empty( $getActivityDetails ) ) {
      $this->_target_id          = $getActivityDetails['id'];
      $this->_target_activity_id = $getActivityDetails['activity_id'];        
      $this->_activity_date_time = $getActivityDetails['activity_date_time'];
    }
  }

  function buildQuickForm(){
    $sql = "
SELECT     a.id as activity_id, a.activity_date_time, a.subject, a.location
FROM       civicrm_activity a
INNER JOIN civicrm_activity_contact aa ON a.id = aa.activity_id 
	AND aa.record_type_id = 1
LEFT  JOIN civicrm_activity_contact at ON a.id = at.activity_id
	AND at.record_type_id = 3
WHERE      a.activity_type_id = %1
AND        aa.contact_id = %2 
AND        a.status_id = 1
AND        a.activity_date_time > NOW()
AND        at.contact_id = %3
ORDER BY   a.activity_date_time asc
";
       
    $params  = array( 1 => array( $this->_actTypeId, 'Integer' ),
                      2 => array( School_APPLICATION_SCHOOL_CONTACT_ID, 'Integer' ),
                      3 => array( $this->_targetContactId,  'Integer' ) );
    $dao     = CRM_Core_DAO::executeQuery( $sql, $params );

    $slots = array( );
    while ( $dao->fetch( ) ) {
      $dateTime = date("D, M j, g:ia" ,strtotime($dao->activity_date_time));

      $slots[$dao->activity_id] = "$dateTime";

    }
        
    $slots = array_unique($slots);
    if ( ! empty( $slots ) ) {
      $label =  ($this->_target_id ?
                 "Reserved slot for {$this->_actType}" : "Choose a slot for {$this->_actType}") ;
      $this->addElement( 'select', 'slot_activity_id', $label, $slots , true );
    } else {
      CRM_Core_Error::statusBounce( "Currently there are no slots available for {$this->_actType}." );
    }

    // check if slot is already created
    if( $this->_target_id  ) {
      $this->addElement('checkbox' ,'cancel_activity', ts('Cancel this Activity')  ) ;
      $defaults['slot_activity_id'] = $this->_target_activity_id;
      $this->setDefaults($defaults);
      $this->freeze(array('slot_activity_id'));
    }

    $this->addButtons( array(
                             array ( 'type'      => 'next',
                                     'name'      => ts('Save'),
                                     'isDefault' => true   ),
                             array ( 'type'      => 'cancel',
                                     'name'      => ts('Cancel') ),
                             )
                       );
  }

  function postProcess() {        
    $params     = $this->controller->exportValues( $this->_name );
    $activityId = CRM_Utils_Array::value( 'slot_activity_id', $params );
    require_once 'CRM/Core/PseudoConstant.php';
   		
    if ( $activityId && !$this->_target_id ) {
      $sql = "
REPLACE INTO civicrm_activity_contact (activity_id, contact_id, record_type_id)
VALUES ( %1, %2, 3) ";
      $params = array( 1 => array( $activityId, 'Integer' ),
                       2 => array( $this->_targetContactId, 'Integer' ) );
      CRM_Core_DAO::executeQuery( $sql, $params );
      $dateTime = CRM_Core_DAO::getFieldValue( 'CRM_Activity_DAO_Activity',  $activityId, 'activity_date_time', 'id' );
      $scheduledTime = CRM_Utils_Date::customFormat( $dateTime, "%l:%M %P on %b %E%f");
      CRM_Core_Session::setStatus(ts(' Your %1 has been scheduled at <b>%2</b>. You will recieve a confirmation shortly.',
                                     array( 1 => $this->_actType , 2 =>  $scheduledTime) ), 'success');
      self::sendMail( $activityId , $this->_actType );            
    } else if( $this->_target_id ) { 
      $dateTime = CRM_Utils_Date::customFormat( $this->_activity_date_time, "%l:%M %P on %b %E%f" );
      CRM_Core_Session::setStatus(ts(' Your %1 that was scheduled at <b>%2</b> has been cancelled. You will recieve a confirmation shortly.',
                                     array( 1 => $this->_actType, 2 => $dateTime) ), 'success');
      $sql = "
DELETE FROM civicrm_activity_contact
WHERE id = %1 AND record_type_id = 3";
      $params = array( 1 => array( $this->_target_id, 'Integer' ));
      CRM_Core_DAO::executeQuery( $sql, $params );
      self::sendMail( $activityId , $this->_actType , true );
    }

  }

  function sendMail($activityID , $activityType , $cancel = false) {
    // send reminder mail to parent
    $templateVars = array( );
    list( $templateVars['schoolName'],
          $templateVars['schoolEmail'] ) = 
      School_Utils_Query::getNameAndEmail( School_APPLICATION_SCHOOL_CONTACT_ID );
    if( $this->_applicantId ) {
      list( $templateVars['childName'],
            $templateVars['childEmail'] )  = School_Utils_Query::getNameAndEmail( $this->_applicantId );
    }
    list( $templateVars['parentName'],
          $templateVars['parentEmail'] ) = School_Utils_Query::getNameAndEmail( $this->_parentId );
        
    $dateTime = CRM_Core_DAO::getFieldValue( 'CRM_Activity_DAO_Activity',
                                             $activityID,
                                             'activity_date_time' );
    $templateVars['dateTime'] = CRM_Utils_Date::customFormat( $dateTime,
                                                              "%l:%M %P on %b %E%f" );
    $template = CRM_Core_Smarty::singleton( );
    $template->assign( $templateVars );
        
    $activityTemplate = ($cancel ? 'Cancel'.$activityType : $activityType );
        
    $template->assign( 'content', 'subject' );        
    $subject  = $template->fetch( "School/Mail/Apply/{$activityTemplate}.tpl");
    $template->assign( 'content', 'message' );
    $message  = $template->fetch( "School/Mail/Apply/{$activityTemplate}.tpl" );
        
    require_once 'CRM/Utils/Mail.php';
        
    $params = array( 'from'    => "{$this->_fromEmail[0]}<{$this->_fromEmail[1]}>",
                     'toName'  => $templateVars['parentName'],
                     'toEmail' => $templateVars['parentEmail'],
                     'subject' => $subject,
                     'cc'      => School_Form_Apply::CC_EMAILID,
                     'text'    => $message,
                     );
    CRM_Utils_Mail::send( $params );
  }
    
}
