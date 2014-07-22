{if $content eq 'subject'}
Reminder: Tour of {$schoolName} at {$dateTime}
{else}{if $content eq 'message'}
Dear {$parentName} :

Your Tour with {$schoolName} is booked for {$dateTime}.

If you are unable to attend the Tour, please contact {$schoolName}{if $schoolEmail} at {$schoolEmail}{/if}.

Thank you

{$schoolName}
{/if}
{/if}
