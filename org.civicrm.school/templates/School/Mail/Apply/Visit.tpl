{if $content eq 'subject'}
Reminder: Child Visit of {$schoolName} at {$dateTime}
{else}{if $content eq 'message'}
Dear {$parentName} :

Your Child Visit for {$parentName} with {$schoolName} is scheduled for {$dateTime}.

If you are unable to attend this meeting, please contact {$schoolName}{if $schoolEmail} at {$schoolEmail}{/if}.

Thank you

{$schoolName}
{/if}
{/if}
