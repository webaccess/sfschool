{if $content eq 'subject'}
Interview Cancelled
{else}{if $content eq 'message'}
Dear {$parentName} :

You have cancelled your meeting at {$schoolName} that was scheduled at {$dateTime}.

Thank you

{$schoolName}
{/if}
{/if}
