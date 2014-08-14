# PK3 North
INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
SELECT 4725, c.id, 10, 1
FROM civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE (s.grade = 'PK3 N')
AND   c.contact_sub_type LIKE '%Student%'
AND   s.is_currently_enrolled = 1
;

# Vahlee
INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
SELECT 209, c.id, 10, 1
FROM civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE (s.grade = 'PK4 N')
AND   c.contact_sub_type LIKE '%Student%'
AND   s.is_currently_enrolled = 1
;

# Dolores
INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
SELECT 454, c.id, 10, 1
FROM civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE (s.grade = 'K N')
AND   c.contact_sub_type LIKE '%Student%'
AND   s.is_currently_enrolled = 1
;

# PK3 South
INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
SELECT 4724, c.id, 10, 1
FROM civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE (s.grade = 'PK3 S')
AND   c.contact_sub_type LIKE '%Student%'
AND   s.is_currently_enrolled = 1
;

# Harald
INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
SELECT 708, c.id, 10, 1
FROM civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE (s.grade = 'PK4 S')
AND   c.contact_sub_type LIKE '%Student%'
AND   s.is_currently_enrolled = 1
;

# Lauren
INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
SELECT 4715, c.id, 10, 1
FROM civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE (s.grade = 'K S')
AND   c.contact_sub_type LIKE '%Student%'
AND   s.is_currently_enrolled = 1
;

# Molly
INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
SELECT 740, c.id, 10, 1
FROM civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE (s.grade = '1')
AND   c.contact_sub_type LIKE '%Student%'
AND   s.is_currently_enrolled = 1
;

# Maggie Day
INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
SELECT 703, c.id, 10, 1
FROM civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE (s.grade = '2')
AND   c.contact_sub_type LIKE '%Student%'
AND   s.is_currently_enrolled = 1
;

# Laura
INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
SELECT 698, c.id, 10, 1
FROM civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE (s.grade = '3')
AND   c.contact_sub_type LIKE '%Student%'
AND   s.is_currently_enrolled = 1
;

# Talia
INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
SELECT 4252, c.id, 10, 1
FROM civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE (s.grade = '4')
AND   c.contact_sub_type LIKE '%Student%'
AND   s.is_currently_enrolled = 1
;

# Francisco
INSERT INTO civicrm_relationship (contact_id_a, contact_id_b, relationship_type_id, is_active )
SELECT 10, c.id, 10, 1
FROM civicrm_contact c
INNER JOIN civicrm_value_school_information s ON c.id = s.entity_id
WHERE (s.grade = '5')
AND   c.contact_sub_type LIKE '%Student%'
AND   s.is_currently_enrolled = 1
;
