INSERT INTO `civicrm_option_group` (`name`, `title`, `description`, `is_reserved`, `is_active`) VALUES
('updated_email', 'Message template for updating email', NULL, 1, 1),
('updated_address', 'Message template for updating address', NULL, 1, 1);

SELECT @option_group_id_mail := id FROM `civicrm_option_group` WHERE `name` LIKE '%updated_email%';
SELECT @option_group_id_addr := id FROM `civicrm_option_group` WHERE `name` LIKE '%updated_address%';

INSERT INTO `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) VALUES
(@option_group_id_mail, 'Email Update', '1', 'email_update_notification', NULL, 0, 0, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
(@option_group_id_addr, 'Address Update', '1', 'address_update_notification', NULL, 0, 0, 1, NULL, 0, 0, 1, NULL, NULL, NULL);

SELECT @option_value_id_mail := id FROM `civicrm_option_value` WHERE `name` LIKE '%email_update_notification%';
SELECT @option_value_id_addr := id FROM `civicrm_option_value` WHERE `name` LIKE '%address_update_notification%';

INSERT INTO `civicrm_msg_template` (`msg_title`, `msg_subject`, `msg_text`, `msg_html`, `is_active`, `workflow_id`, `is_default`, `is_reserved`, `pdf_format_id`) VALUES
('Updated Email', 'Your Email is updated', 'Your email id has been updated.', '<p>Your email id has been updated.</p>', 1, @option_value_id_mail, 1, NULL, NULL),
('Updated Address', 'Your Address has been updated', 'Your address has been updated.\r\n                                   Your new address is :\r\n                                   {if  array_key_exists("street_address",$address_new)}{$address_new.street_address}{/if}<br>\r\n                                         {if array_key_exists("city",$address_new)}{$address_new.city}{/if} {if array_key_exists("state_province_id",$address_new)}{$address_new.state_province_id}{/if} {if array_key_exists("postal_code",$address_new)}{$address_new.postal_code}{/if} {if array_key_exists("postal_code_suffix",$address_new) } - {$address_new.postal_code_suffix}{/if}<br>\r\n                                         {if array_key_exists("country_id",$address_new)}{$address_new.country_id}{/if}', '<p>You have updated your address.</p>\r\n                                                           <p>Your new address is :</p>                                 \r\n                                         <p>{if  array_key_exists("street_address",$address_new)}{$address_new.street_address}{/if}<br>\r\n                                         {if array_key_exists("city",$address_new)}{$address_new.city}{/if} {if array_key_exists("state_province_id",$address_new)}{$address_new.state_province_id}{/if} {if array_key_exists("postal_code",$address_new)}{$address_new.postal_code}{/if} {if array_key_exists("postal_code_suffix",$address_new) } - {$address_new.postal_code_suffix}{/if}<br>\r\n                                         {if array_key_exists("country_id",$address_new)}{$address_new.country_id}{/if}</p>', 1, @option_value_id_addr, 1, NULL, NULL);
