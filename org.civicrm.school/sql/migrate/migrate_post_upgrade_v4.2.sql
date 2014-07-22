UPDATE  `civicrm_contact`, `civicrm_value_school_information` 
SET `civicrm_contact`.contact_sub_type = CONCAT( '',`civicrm_value_school_information`.subtype,'' )
WHERE `civicrm_contact`.id = `civicrm_value_school_information`.entity_id;

DELETE FROM `civicrm_custom_field` WHERE `civicrm_custom_field`.name = 'SubType';

ALTER TABLE `civicrm_value_school_information` DROP COLUMN subtype;

SELECT @option_group_id := id from civicrm_option_group where name = 'report_template';
UPDATE civicrm_option_value SET name = REPLACE(name, "SFS_", "School_") WHERE option_group_id = @option_group_id;

UPDATE civicrm_report_instance
SET civicrm_report_instance.report_id = REPLACE(civicrm_report_instance.report_id, "sfschool", "school")
WHERE civicrm_report_instance.report_id LIKE "%sfschool%";

UPDATE civicrm_option_value
SET civicrm_option_value.value = REPLACE(civicrm_option_value.value, "sfschool", "school")
WHERE civicrm_option_value.value LIKE "%sfschool%" AND civicrm_option_value.option_group_id = @option_group_id;

DROP TABLE IF EXISTS `school_extended_care_source`;
ALTER TABLE sfschool_extended_care_source
RENAME TO school_extended_care_source;

