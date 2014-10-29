Good day,

The e-mail you received yesterday regarding the extended day usage reminder is for this school year 2014-15 through yesterday and not 2013-14.  We apologize for the misunderstanding.

The data on the Extended Day Care has been updated to include the Fall activity fee. Our records show that your child’s extended day care account has a balance. If you have any questions or believe our records are incorrect please call or email the Business Office at 239-1410 or business@sfschool.org. Checks are payable to The San Francisco School, 300 Gaven Street, San Francisco, CA 94134 and please note your child's name in the memo line.

Below is an overview of your Daycare Usage . To review the details of your Extended Day Account please logon to the SFS Parent Portal at http://sfschool.org/drupal and click on View Extended Care block charges.

Total Blocks Paid: {$totalPayments}

Total Blocks Charged (Standard + Activity Blocks): {$totalCharges}
    >> Standard Extended Care Charges: {$blockCharges}
    >> Activity Class Charges: {$classCharges}

Block Balance Due: {$balanceDue}

{assign var=totalFull value=`$balanceDue*13.50`}
{assign var=totalInd value=`$balanceDue*11.50`}
Block Balance Due In Dollars
    >> Full Pay or Paying Under 100 Blocks @ $13.50 per block: {$totalFull|crmMoney}
    >> Indexed Tuition or Paying Over 100 Blocks @ $11.50 per block: {$totalInd|crmMoney}
