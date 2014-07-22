{if $content eq 'subject'}
Child Visit Cancelled
{else}{if $content eq 'message'}
Dear {$parentName} :

You have cancelled your visit at {$schoolName} that was scheduled at {$dateTime}.

Thank you

{$schoolName}
{/if}
{/if}
