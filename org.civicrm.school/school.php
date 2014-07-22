<?php

// FIXME: remove any unused constants
define( 'School_APPLICATION_CONTRIBUTION_PAGE_ID', null );
define( 'School_APPLICATION_SCHOOL_CONTACT_ID', null);

function school_civicrm_install( ) {
  $dirRoot =dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
  $dirsql = $dirRoot . DIRECTORY_SEPARATOR .'sql'. DIRECTORY_SEPARATOR .'install'. DIRECTORY_SEPARATOR .'civicrm_school.mysql';
  CRM_Utils_File::sourceSQLFile( CIVICRM_DSN, $dirsql );
  $dirsqljob = $dirRoot . DIRECTORY_SEPARATOR .'sql'. DIRECTORY_SEPARATOR .'install'. DIRECTORY_SEPARATOR .'schoolJobs.sql';
  CRM_Utils_File::sourceSQLFile( CIVICRM_DSN, $dirsqljob );

  $contactTypes = CRM_Contact_BAO_ContactType::contactTypes();
  $createContactSubtypeParams = array(
    'parent_id' => 1,
    'is_active' => 1,
    'image_URL' => '',
    'description' => '' ,
  );
  $subTypes = array( 'Student', 'Parent', 'Staff', 'Applicant', 'Applicant_Parent', 'Teacher' );
  for( $i=0; $i < sizeOf( $subTypes ); $i++ ) {
    if( !in_array( $subTypes[$i], $contactTypes ) ) {
      $createContactSubtypeParams['label'] = $subTypes[$i];
      $createContactSubtypeParams['name'] = $subTypes[$i];
      CRM_Contact_BAO_ContactType::add( $createContactSubtypeParams );
    }
  }
  $xml_file = $dirRoot . 'sql' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'SFSModuleData.xml';
  require_once 'CRM/Utils/Migrate/Import.php';
  $import = new CRM_Utils_Migrate_Import();
  $import->run( $xml_file );
  CRM_Core_Invoke::rebuildMenuAndCaches( );
}

function school_civicrm_config( &$config ) {
  $template =& CRM_Core_Smarty::singleton( );

  //check whether the user is just logged or not
  if ("Log in" == CRM_Utils_Array::value('op', $_REQUEST)) {
    global $user;
    $login_user_cid = new CRM_Core_DAO_UFMatch( );
    $login_user_cid->uf_id = $user->uid;
    $login_user_cid->find(true);
    $ContactSubtypes = CRM_Contact_BAO_Contact::getContactSubType($login_user_cid->contact_id);

    if( !empty( $ContactSubtypes ) ){
      if( in_array( "Parent", $ContactSubtypes ) ){
        CRM_Utils_System::redirect( CRM_Utils_System::url( "civicrm/profile/view", "reset=1&gid=3&id=".$login_user_cid->contact_id ) );
      }
      else if(  in_array( "Staff", $ContactSubtypes )  ){
        CRM_Utils_System::redirect( CRM_Utils_System::url(  "civicrm/profile/edit", "reset=1&gid=3&id=".$login_user_cid->contact_id ) );
      }
    }
  }

  $schoolRoot =
    dirname( __FILE__ ) . DIRECTORY_SEPARATOR ;

  $schoolDir = $schoolRoot . 'templates';

  if ( is_array( $template->template_dir ) ) {
    array_unshift( $template->template_dir, $schoolDir );
  } else {
    $template->template_dir = array( $schoolDir, $template->template_dir );
  }

  // also fix php include path
  $include_path = $schoolRoot . PATH_SEPARATOR . get_include_path( );
  set_include_path( $include_path );

  // assign the profile ids
  $gidStudent = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_UFGroup', 'Student_Information', 'id', 'name' );
  $gidParent = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_UFGroup', 'Parent_Information', 'id', 'name' );
  $template->assign( 'parentProfileID' , $gidParent  );
  $template->assign( 'studentProfileID', $gidStudent );

  // set the timezone
  date_default_timezone_set('America/Los_Angeles');
}

function school_civicrm_pageRun( &$page ) {
  $name = $page->getVar( '_name' );
  $gid = null;
  if ( $name == 'CRM_Profile_Page_Dynamic' ) {
    $gid = $page->getVar( '_gid' );
    $gname = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_UFGroup', $gid, 'name', 'id' );
    switch ( $gname ) {
      case "Parent_Information":
        return _school_civicrm_pageRun_Profile_Page_Dynamic_Parent_Information( $page, $gid );
      case "Student_Information":
        return _school_civicrm_pageRun_Profile_Page_Dynamic_Student_Information( $page, $gid );
      case "Participant_Status":
        return _school_civicrm_pageRun_Profile_Page_Dynamic_Participant_Status( $page, $gid );
    }
  } else if ( $name == 'CRM_Contact_Page_View_CustomData' ) {
    if ( $page->getVar( '_groupId' ) != $gid ) {
      return;
    }

    // get the details from smarty
    $smarty  =& CRM_Core_Smarty::singleton( );
    $details =& $smarty->get_template_vars( 'viewCustomData' );

    require_once 'School/Utils/ExtendedCare.php';
    School_Utils_ExtendedCare::sortDetails( $details );

    // CRM_Core_Error::debug( 'POST', $details );
    $smarty->assign_by_ref( 'viewCustomData', $details );
  }
}

function _school_civicrm_pageRun_Profile_Page_Dynamic_Parent_Information( &$page, $gid ) {
  $parentID = $page->getVar( '_id' );
  if ( !CRM_Contact_BAO_Contact_Permission::allow( $parentID ) && !CRM_Core_Permission::check( 'administer CiviCRM' ) ) {
    CRM_Core_Error::fatal( ts('Not enough permission.') );
  }
  $values = array( );

  $onlyDriver = CRM_Utils_Request::retrieve( 'driver', 'Integer', $page, false, false );
  $page->assign( 'onlyDriver', $onlyDriver);

  require_once 'School/Utils/Query.php';

  School_Utils_Query::checkSubType( $parentID, array( 'Parent', 'Staff' ) );

  require_once 'School/Utils/Relationship.php';
  School_Utils_Relationship::getChildren( $parentID,
    $values,
    true );
  $childrenIDs = array_keys( $values );

  require_once 'School/Utils/Conference.php';
  School_Utils_Conference::getValues( $childrenIDs, $values, false, $parentID );

  require_once 'School/Utils/ReportCard.php';
  School_Utils_ReportCard::getValues( $childrenIDs, $values );

  require_once 'School/Utils/ExtendedCare.php';
  School_Utils_ExtendedCare::getValues( $childrenIDs, $values, $parentID );

  foreach ( $childrenIDs as $childID ) {
    $values[$childID]['familyURL'] =
      CRM_Utils_System::url( "civicrm/school/family/household",
        "reset=1&cid={$childID}&pid={$parentID}" );
  }

  $page->assign( 'childrenInfo', $values );
  require_once 'CRM/Contact/BAO/Contact.php';
  $subType = CRM_Contact_BAO_Contact::getContactSubType($parentID);

  if (in_array('Staff',$subType)) {
    $ptcValues = array( );
    School_Utils_Conference::getPTCValuesOccupied( $parentID, $ptcValues );

    $page->assign( 'ptcValues', $ptcValues );
  }

  // $vehicleFieldIds = school_vehicleFields();
  // $page->assign( 'vehicleFieldIds', $vehicleFieldIds );
}

function _school_civicrm_pageRun_Profile_Page_Dynamic_Student_Information( &$page, $gid ) {
  $childID = $page->getVar( '_id' );
  if ( !CRM_Contact_BAO_Contact_Permission::allow( $childID ) && !CRM_Core_Permission::check( 'administer CiviCRM' ) ) {
    CRM_Core_Error::fatal( ts('Not enough permission.') );
  }

  $term =  CRM_Utils_Request::retrieve( 'term', 'String', $page, false, null );

  require_once 'School/Utils/Query.php';
  School_Utils_Query::checkSubType( $childID, 'Student');

  $values = array( );
  $values[$childID] =
    array(
      'name'    =>
      CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact', $childID, 'display_name' ),
      'grade'   => School_Utils_Query::getGrade( $childID ),
      'parents' => array( ) );

  require_once 'School/Utils/Relationship.php';
  School_Utils_Relationship::getParents( $childID,
    $values[$childID]['parents'],
    false );
  require_once 'CRM/Core/Permission.php';

  require_once 'School/Utils/ReportCard.php';
  School_Utils_ReportCard::getValues( $childID, $values );

  require_once 'School/Utils/Conference.php';
  School_Utils_Conference::getValues( $childID, $values );

  require_once 'School/Utils/ExtendedCare.php';
  School_Utils_ExtendedCare::getValues( $childID, $values, null, $term );

  // use the first parent by default (since we are admin)
  $parentIDs = array_keys( $values[$childID]['parents'] );
  if ( empty( $parentIDs ) ) {
    CRM_Core_Error::fatal( );
  }

  // require_once 'School/Utils/ReportCard.php';
  // School_Utils_ReportCard::getValues( $childID, $values, CIVICRM_SCHOOL_YEAR );

  $values[$childID]['familyURL'] =
    CRM_Utils_System::url( "civicrm/school/family/household",
      "reset=1&cid={$childID}&pid={$parentIDs[0]}" );

  $page->assign( 'childInfo', $values[$childID] );
}

function _school_civicrm_pageRun_Profile_Page_Dynamic_Participant_Status( &$page, $gid ) {
  // get the details from smarty
  $smarty =& CRM_Core_Smarty::singleton( );
  $row    =& $smarty->get_template_vars( 'row' );

  $childID = $page->getVar( '_id' );

  require_once 'School/Utils/Intake.php';
  School_Utils_Intake::unscrambleProfileRow( $row, $childID );

  $smarty->assign_by_ref( 'row', $row );
}

function school_civicrm_buildForm( $formName, &$form ) {
  if ( $formName == 'CRM_Contribute_Form_Contribution_Main' ) {
    $values = $form->getVar( '_values' );
    $params = array('name' =>'Donation' );
    $contrib = CRM_Contribute_BAO_ContributionType::retrieve($params);
    if ( $values['contribution_type_id'] == $contrib->id && !$form->get( 'appID' ) ) {
      $appID = CRM_Utils_Request::retrieve( 'appid', 'Positive', $this, true );
      $form->set( 'appID', $appID );
    }
  }
  if ( $formName == 'CRM_Profile_Form_Edit' ) {
    $gid = $form->getVar( '_gid' );
    $gname = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_UFGroup', $gid, 'name', 'id' );
    switch ( $gname ) {
      case "Parent_Information":
        return _school_civicrm_buildForm_CRM_Profile_Form_Edit_Parent_Information( $formName, $form, $gid );
      case "Student_Information":
        return _school_civicrm_buildForm_CRM_Profile_Form_Edit_Student_Information( $formName, $form, $gid );
      case "Participant_Status":
        return _school_civicrm_buildForm_CRM_Profile_Form_Edit_Participant_Status( $formName, $form, $gid );
    }
  } else if ( $formName == 'CRM_Contact_Form_Merge' &&
    empty( $_POST ) ) {
    // do this only for GET requests on the merge form
    $cid = CRM_Utils_Array::value( 'cid', $_GET );
    $oid = CRM_Utils_Array::value( 'oid', $_GET );
    if ( ! $cid || !$oid ) {
      return;
    }

    // check if $oid has a drupal user, if so set a warning
    $sql = "
SELECT id
FROM   civicrm_uf_match
WHERE  contact_id = %1
";
    $params = array( 1 => array( $oid, 'Integer' ) );
    $ufID = CRM_Core_DAO::singleValueQuery( $sql, $params );
    if ( $ufID ) {
      $session =& CRM_Core_Session::singleton( );
      $session->setStatus( ts( 'The contact that will be deleted has a user record (%1) associated with it',
          array( 1 => $ufID ) ) );
    }
  }
  if ( $formName == 'CRM_Profile_Form_Dynamic' ) {
    foreach ( $form->getVar('_fields') as $key => $value ) {
      if( CRM_Utils_Array::value('groupTitle', $value) ) {
	$groupTitle = strtolower(trim(CRM_Utils_Array::value('groupTitle', $value)));
	break;
      }
    }
    $parentString = 'Parent Registration';
    if ( $groupTitle == strtolower(trim($parentString)) ) {
      $form->removeElement('contact_sub_type');
      $element =& $form->add( 'hidden', 'contact_sub_type' );
      $element->setValue('Applicant_Parent');
    }
  }
}

function school_civicrm_validate( $formName, &$fields, &$files, &$form ) {
  if ( $formName == 'CRM_Profile_Form_Edit' ) {
    $gid = $form->getVar( '_gid' );
    $gname = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_UFGroup', $gid, 'name', 'id' );
    if ( $gname = "Parent_Information" ) {
      require_once 'School/Utils/Conference.php';
      return School_Utils_Conference::validatePTCForm( $form, $fields );
    }
  }
  return null;
}

function school_civicrm_pre( $op, $objectName, $id, &$params ) {
  if( $op == "edit" && $objectName == "Profile" && $params['contact_sub_type_hidden'] == "Parent" ) {
    // unset contact subtype on profile edit if multiple contact sub types
    $contactSubtype = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact', $id, 'contact_sub_type', 'id' );
    $params['contact_sub_type_hidden'] = $contactSubtype;
    $agreementDateId = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomField', 'Agreement Date', 'id', 'label' );
    if( $agreementDateId ) {
      $params['custom_'.$agreementDateId] = date('Y-m-d');
      $params['custom_'.$agreementDateId.'_time'] = date('H:i:s');
    }
  }
}

function school_civicrm_postProcess( $class, &$form ) {
  if ( is_a( $form, 'CRM_Contribute_Form_Contribution_Confirm' ) ) {
    // process an applicants payment
    $values = $form->getVar( '_values' );
    $appID  = $form->get( 'appID' );
    $params = array('name' =>'Donation' );
    $contrib = CRM_Contribute_BAO_ContributionType::retrieve($params);
    if ( $values['contribution_type_id'] == $contrib->id &&
      $appID && $form->_params['trxn_id'] ) {
      require_once 'School/Form/Apply.php';
      $contributionId = CRM_Core_DAO::getFieldValue( "CRM_Contribute_DAO_Contribution",
                        $form->_params['trxn_id'], 'id', 'trxn_id' );
      $query    = "SELECT cf.id FROM civicrm_custom_field cf
INNER JOIN civicrm_custom_group cg ON cg.id=cf.custom_group_id AND cg.table_name = '". School_Form_Apply::CUSTOM_APPLICANT_TABLE . "' AND cf.column_name = 'payment_id'
LIMIT 1";
      $customId = CRM_Core_DAO::singleValueQuery( $query );
      $customParams = $customFields = array();
      $customParams["custom_{$customId}-1"] = $contributionId;
      CRM_Core_BAO_CustomValueTable::postProcess( $customParams,
        $customFields,
        'civicrm_contact',
        $appID,
        'Individual' );
    }
  }
  if ( is_a( $form, 'CRM_Profile_Form_Edit' ) ) {
    $gid = $form->getVar( '_gid' );
    $id  = $form->getVar( '_id' );
    $gname = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_UFGroup', $gid, 'name', 'id' );

    switch ( $gname ) {
      case "Parent_Information" :
        $session = CRM_Core_Session::singleton();
        $session->popUserContext();
        $session->pushUserContext(CRM_Utils_System::url('civicrm/profile/view', "reset=1&gid={$gid}&snippet=2&id={$id}&driver=1"));
        return school_civicrm_postProcess_CRM_Profile_Form_Edit_Parent_Information( $class, $form, $gid );
      case "Student_Information" :
        return school_civicrm_postProcess_CRM_Profile_Form_Edit_Student_Information( $class, $form, $gid );
    }
  }


}

function _school_civicrm_buildForm_CRM_Profile_Form_Edit_Parent_Information( $formName, &$form, $gid ) {
  $staffID   = $form->getVar( '_id' );

  $freezeElements = array( );
  $elementList = array( 'first_name', 'last_name', 'email-Primary', 'phone-Primary' );
  foreach( $elementList as $key => $val ) {
    if( array_key_exists( $val, $form->_defaultValues ) ) {
      $freezeElements[] = $val;
    }
  }

  // freeze first name, last name and grade
  $form->freeze( $freezeElements );

  require_once 'School/Utils/Conference.php';
  School_Utils_Conference::buildPTCForm( $form, $staffID );

  // $vehicleFieldIds = school_vehicleFields();
  // $form->assign( 'vehicleFieldIds', $vehicleFieldIds );
}

function _school_civicrm_buildForm_CRM_Profile_Form_Edit_Student_Information( $formName, &$form, $gid ) {
  // get the custom field if for grade
  require_once 'CRM/Core/BAO/CustomField.php';
  $gradeFieldID = CRM_Core_BAO_CustomField::getCustomFieldID('Grade', 'School Information');

  // freeze first name, last name and grade
  $elementList = array( 'first_name', 'last_name', "custom_{$gradeFieldID}" );
  $form->freeze( $elementList );

  $childID   = $form->getVar( '_id' );

  require_once 'School/Utils/Conference.php';
  School_Utils_Conference::buildForm( $form, $childID );

  $term =  CRM_Utils_Request::retrieve( 'term', 'String',
           $form, false, null );

  require_once 'School/Utils/ExtendedCare.php';
  School_Utils_ExtendedCare::buildForm( $form, $childID, $term );
}


function _school_civicrm_buildForm_CRM_Profile_Form_Edit_Participant_Status( $formName, &$form, $gid ) {
  $childID   = $form->getVar( '_id' );

  require_once 'School/Utils/Intake.php';
  School_Utils_Intake::buildForm( $form, $childID );
}

function school_civicrm_postProcess_CRM_Profile_Form_Edit_Parent_Information( $class, &$form, $gid ) {
  $staffID   = $form->getVar( '_id' );

  require_once 'School/Utils/Conference.php';
  School_Utils_Conference::postProcessPTC( $form, $staffID );
}


function school_civicrm_postProcess_CRM_Profile_Form_Edit_Student_Information( $class, &$form, $gid ) {
  require_once 'School/Utils/Conference.php';
  School_Utils_Conference::postProcess( $class, $form, $gid );

  $term =  CRM_Utils_Request::retrieve( 'term', 'String',
           $form, false, null );

  require_once 'School/Utils/ExtendedCare.php';
  School_Utils_ExtendedCare::postProcess( $class, $form, $gid, $term );
}

function school_civicrm_tabs( &$tabs, $contactID ) {
  require_once 'School/Utils/Query.php';
  $subType = CRM_Contact_BAO_Contact::getContactSubType($contactID);


  // if subType is not student then hide the extended care tab
  if(in_array('Student', $subType)) {
    return;
  }

  foreach ( $tabs as $tabID => $tabValue ) {
    if ( $tabValue['title'] == 'Extended Care' ||
      $tabValue['title'] == 'Extended Care Signout' ) {
      unset( $tabs[$tabID] );
    }
  }
}

function school_civicrm_xmlMenu( &$files ) {
  $files[] =
    dirname( __FILE__ ) . DIRECTORY_SEPARATOR .
    'School'               . DIRECTORY_SEPARATOR .
    'xml'               . DIRECTORY_SEPARATOR .
    'Menu'              . DIRECTORY_SEPARATOR .
    'school.xml';
}

function school_civicrm_navigationMenu( &$params ) {
  // Get the student information profile id
  $gidStudent = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_UFGroup', 'Student_Information', 'id', 'name' );

  //  Get the maximum key of $params
  $maxKey = ( max( array_keys($params) ) );

  $params[$maxKey+1] =
    array (
      'attributes' => array (
        'label'      => 'School',
        'name'       => 'School',
        'url'        => null,
        'permission' => 'access CiviCRM',
        'operator'   => null,
        'separator'  => null,
        'parentID'   => null,
        'navID'      => $maxKey+1,
        'active'     => 1
      ),
      'child' =>
      array (
        '1' => array (
          'attributes' => array (
            'label'      => 'Student Search',
            'name'       => 'Student Search',
            'url'        => CRM_Utils_System::url( 'civicrm/profile',

                          'reset=1&gid=' .$gidStudent, true,
                          null, false ),
            'permission' => 'access CiviCRM',
            'operator'   => null,
            'separator'  => 1,
            'parentID'   => $maxKey+1,
            'navID'      => 1,
            'active'     => 1
          ),
          'child' => null
        ),
        '2' => array (
          'attributes' => array (
            'label'      => 'Extended Care Summary',
            'name'       => 'Extended Care Summary',
            'url'        => CRM_Utils_System::url( 'civicrm/school/extendedCareSummary',
                          'reset=1', true,
                          null, false ),
            'permission' => 'access CiviCRM',
            'operator'   => null,
            'separator'  => 1,
            'parentID'   => $maxKey+1,
            'navID'      => 1,
            'active'     => 1
          ),
          'child' => null
        ),
        '3' => array (
          'attributes' => array (
            'label'      => 'Extended Care Class Listings',
            'name'       => 'Extended Care Class Listings',
            'url'        => CRM_Utils_System::url( 'civicrm/school/extended/class',
                          'reset=1', true,
                          null, false ),
            'permission' => 'access CiviCRM',
            'operator'   => null,
            'separator'  => 1,
            'parentID'   => $maxKey+1,
            'navID'      => 1,
            'active'     => 1
          ),
          'child' => null
        ),
        '4' => array (
          'attributes' => array (
            'label'      => 'Extended Care Class Detail',
            'name'       => 'Extended Care Class Detail',
            'url'        => CRM_Utils_System::url( 'civicrm/report/school/extended/roster',
                          'reset=1', true,
                          null, false ),
            'permission' => 'access CiviCRM',
            'operator'   => null,
            'separator'  => 1,
            'parentID'   => $maxKey+1,
            'navID'      => 1,
            'active'     => 1
          ),
          'child' => null
        ),
        '5' => array (
          'attributes' => array (
            'label'      => 'Class Driver Details',
            'name'       => 'Class Driver Details',
            'url'        => CRM_Utils_System::url( 'civicrm/report/school/driverDetails',
                          'reset=1', true,
                          null, false ),
            'permission' => 'access CiviCRM',
            'operator'   => null,
            'separator'  => 1,
            'parentID'   => $maxKey+1,
            'navID'      => 1,
            'active'     => 1
          ),
          'child' => null
        ),
        '6' => array (
          'attributes' => array (
            'label'      => 'Reports',
            'name'       => 'Reports',
            'url'        => CRM_Utils_System::url( 'civicrm/report/list',
                          'reset=1', true,
                          null, false ),
            'permission' => 'access CiviCRM',
            'operator'   => null,
            'separator'  => 1,
            'parentID'   => $maxKey+1,
            'navID'      => 1,
            'active'     => 1
          ),
          'child' => null
        ),
        '7' => array (
          'attributes' => array (
            'label'      => 'Afternoon Signout',
            'name'       => 'Afternoon Signout',
            'url'        => CRM_Utils_System::url( 'civicrm/school/signout',
                          'reset=1', true,
                          null, false ),
            'permission' => 'access CiviCRM',
            'operator'   => null,
            'separator'  => 1,
            'parentID'   => $maxKey+1,
            'navID'      => 1,
            'active'     => 1
          ),
          'child' => null
        ),
        '8' => array (
          'attributes' => array (
            'label'      => 'Afternoon SignIn',
            'name'       => 'Afternoon SignIn',
            'url'        => CRM_Utils_System::url( 'civicrm/school/signin',
                          'reset=1', true,
                          null, false ),
            'permission' => 'access CiviCRM',
            'operator'   => null,
            'separator'  => 1,
            'parentID'   => $maxKey+1,
            'navID'      => 1,
            'active'     => 1
          ),
          'child' => null
        ),
        '9' => array (
          'attributes' => array (
            'label'      => 'Morning SignIn',
            'name'       => 'Morning SignIn',
            'url'        => CRM_Utils_System::url( 'civicrm/school/morning',
                          'reset=1', true,
                          null, false ),
            'permission' => 'access CiviCRM',
            'operator'   => null,
            'separator'  => 1,
            'parentID'   => $maxKey+1,
            'navID'      => 1,
            'active'     => 1
          ),
          'child' => null
        ),
        '10' => array (
          'attributes' => array (
            'label'      => 'Setup Parent teacher conference',
            'name'       => 'Setup Parent teacher conference',
            'url'        => CRM_Utils_System::url( 'civicrm/school/conference',
                          'reset=1', true,
                          null, false ),
            'permission' => 'access CiviCRM',
            'operator'   => null,
            'separator'  => 1,
            'parentID'   => $maxKey+1,
            'navID'      => 1,
            'active'     => 1
          ),
          'child' => null
        ),
        '11' => array (
          'attributes' => array (
            'label'      => 'Batch Consent Form',
            'name'       => 'Batch Consent Form',
            'url'        => CRM_Utils_System::url( 'civicrm/school/batchConsent',
                          'reset=1', true,
                          null, false ),
            'permission' => 'access CiviCRM',
            'operator'   => null,
            'separator'  => 1,
            'parentID'   => $maxKey+1,
            'navID'      => 1,
            'active'     => 1
          ),
          'child' => null
        ),
      )
    );
}
