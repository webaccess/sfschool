#!/bin/bash
pushd ~/git/sfschool
git commit -a -m 'more SFS fixes' .
git push master
popd
pushd /tmp
rm -rf sfschool
git clone https://github.com/webaccess/sfschool.git
cd sfschool
tar czf org.civicrm.school.tgz org.civicrm.school
scp org.civicrm.school.tgz sfschool@sfschool.org:/home/sfschool/www/drupal/sites/all/modules/extensions
ssh sfschool@sfschool.org "cd /home/sfschool/www/drupal/sites/all/modules/extensions ; tar xzf org.civicrm.school.tgz"
popd
