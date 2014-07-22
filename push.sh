#!/bin/bash
pushd ~/svn/school
svn commit -m 'more SFS fixes' .
svn up .
popd
pushd /tmp
rm -rf org.civicrm.school org.civicrm.school.tgz
svn export http://svn.civicrm.org/sfschool/trunk/org.civicrm.school org.civicrm.school
tar czf org.civicrm.school.tgz org.civicrm.school
scp org.civicrm.school.tgz sfschool@sfschool.org:/home/sfschool/www/drupal/sites/all/modules/extensions
ssh sfschool@sfschool.org "cd /home/sfschool/www/drupal/sites/all/modules/extensions ; tar xzf org.civicrm.school.tgz"
popd

