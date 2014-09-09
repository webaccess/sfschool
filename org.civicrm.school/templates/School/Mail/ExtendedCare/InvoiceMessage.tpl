Good day,

Our records show that your child’s extended day care account still has a balance.  Please send in a check to pay the balance as soon as possible as we are wrapping up 2013-14 school year.   if you have any questions or believe our records are incorrect please call or email the Business Office at 239-1410 or business@sfschool.org. Checks are payable to The San Francisco School, 300 Gaven Street, San Francisco, CA 94134 and please note your child's name in the memo line.

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
