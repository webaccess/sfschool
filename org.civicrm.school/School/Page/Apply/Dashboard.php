<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
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
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'api/api.php';
require_once 'School/Form/Apply.php';
/**
 * Page for displaying Dashboard
 */
class School_Page_Apply_Dashboard extends CRM_Core_Page 
{
     protected $_parentId;	

    function browse( ) {
     
        $session          = & CRM_Core_Session::singleton( );
        $userId           = $session->get( 'userID' );

        $this->_visit     = CRM_Core_OptionGroup::getValue( 'activity_type','Visit');
        $this->_interview = CRM_Core_OptionGroup::getValue( 'activity_type','Interview');
        $this->_tour      = CRM_Core_OptionGroup::getValue( 'activity_type','Tour');
        $this->_parentId  = CRM_Utils_Request::retrieve( 'pid', 'Positive', $this, false, $userId, 'REQUEST' );
        
        // make sure logged in user is either - parent OR admin
        if ( $userId != $this->_parentId && 
             !CRM_Core_Permission::check( 'administer CiviCRM' ) ) {
          CRM_Core_Error::fatal( ts('Not enough permission.') );
        }
        
        $contactParams = array( 'id' =>  $this->_parentId );
        CRM_Contact_BAO_Contact::retrieve( $contactParams , $parentInfo ) ;
        
        $session->pushUserContext( CRM_Utils_System::url( 'civicrm/school/apply', 'reset=1' ) );
        $relTypeParams = array(
                               'name_a_b' => 'Child of',
                               );
	$default = null;
	$relType = CRM_Contact_BAO_RelationshipType::retrieve($relTypeParams, $default);
	$relationParams = array( 
			'version' => 3,
			'contact_id_b'=> $this->_parentId,
			'relationship_type_id' => $relType->id,
			 ); 

	$relationships = civicrm_api( 'relationship','get',$relationParams );

        $currentDate = date('Y\-m\-d H\:i\:s' , strtotime("now"));
        $applicants = array();
	if( !empty($relationships['values'])){
            foreach( $relationships['values'] as $key => $value ) {
	      $cid            = $value['contact_id_a'];
	      $isSubType = CRM_Contact_BAO_Contact::getContactSubType( $cid );
        if(in_array('Applicant',$isSubType)){
		$paymentDetails = School_Form_Apply::getPaymentDetails( $cid );
		$getAppInfo     = School_Form_Apply::isApplicationFrozen( $cid );
		$applicants[$cid]['is_app_frozen'] =  $getAppInfo ;
		if( ! empty( $paymentDetails ) ) {
		  $applicants[$cid]['payment'] = $paymentDetails;
		  $paidApplicantId             = $cid;
		} else {
		  $applicants[$cid]['payment_url'] =
                            CRM_Utils_System::url("civicrm/contribute/transact",
                                                  "reset=1&id=" . School_APPLICATION_CONTRIBUTION_PAGE_ID . "&appid=$cid") ;
                    }
		    
		     $applicantDisplayName = CRM_Contact_BAO_Contact::displayName( $cid );
                    $applicants[$cid]['payment_reqd']  = School_Form_Apply::isPaymentRequired( $cid );
                    $applicants[$cid]['display_name']  = ucwords( $applicantDisplayName );
                    $applicants[$cid]['app_complete']  = School_Form_Apply::checkApplicantStatus( $cid ,$this->_parentId );
                    $applicants[$cid]['app_url'  ]     = CRM_Utils_System::url( "civicrm/school/apply/applicant",
                                                                                "reset=1&cid=$cid" );
                    $applicants[$cid]['visit_url']     = CRM_Utils_System::url( "civicrm/school/apply/reserve",
                                                                                "reset=1&cid=$cid&atid={$this->_visit}" );
                    
                    $checkPresentSlot = School_Form_Apply::getActivityDetails ( $this->_visit, $cid , false) ;
                    if ( in_array('Visit',$checkPresentSlot) ) {
                        $applicants[$cid]['visit']                 = $checkPresentSlot;
                        $visitDate                                 = date('Y\-m\-d H\:i\:s' ,
                                                                          strtotime($checkPresentSlot['activity_date_time']));
                        $applicants[$cid]['visit']['is_cancel_url'] = ( $visitDate >= $currentDate ? true : false );
                    }
                }
            }
        }
          if(empty($paidApplicantId)) {
	  if(empty($cid)) {
	    $paidApplicantId = $this->_parentId;
	  }else{
	    $paidApplicantId = $cid;
	  }	  
	}
	   
        $parentInfo['dashboard']['interview_url'] = 
            CRM_Utils_System::url( "civicrm/school/apply/reserve",
                                   "reset=1&atid={$this->_interview}&cid={$paidApplicantId}" );
        $parentInfo['dashboard']['tour_url']      = 
            CRM_Utils_System::url( "civicrm/school/apply/reserve",
                                   "reset=1&atid={$this->_tour}" );
        
        foreach( array( $this->_interview, $this->_tour ) as $value ) {  
              if(!empty($value)){
            $checkPresentSlot = School_Form_Apply::getActivityDetails ( $value, $this->_parentId , false ) ;
          
            
            
            if ( in_array('Tour',$checkPresentSlot )) {
              
              $parentInfo['dashboard']['tour']                  = $checkPresentSlot;               
              $tourDate                                         = date('Y\-m\-d H\:i\:s' ,
                                                                       strtotime($checkPresentSlot['activity_date_time']));  
              
              $parentInfo['dashboard']['tour']['is_cancel_url'] = ( $tourDate >= $currentDate ? true : false ); 
            }  
            if ( in_array('Interview',$checkPresentSlot )) {
              $parentInfo['dashboard']['interview']                  = $checkPresentSlot;
              $interviewDate                                         = date('Y\-m\-d H\:i\:s' ,
                                                                            strtotime($checkPresentSlot['activity_date_time']));                
              $parentInfo['dashboard']['interview']['is_cancel_url'] =  ( $interviewDate >= $currentDate ? true : false );
            } 
                      }
            }
        $this->assign('applicants', $applicants );
        $this->assign('parentInfo', $parentInfo );
        
    }

    function run( ) {

        $action = CRM_Utils_Request::retrieve('action', 'String',
                                              $this, false, 0 );
        $this->assign('action', $action ); 
     
        $this->browse();          
        parent::run();

    }    
}