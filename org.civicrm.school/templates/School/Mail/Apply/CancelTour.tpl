{if $content eq 'subject'}
Tour Cancelled
{else}{if $content eq 'message'}
Dear {$parentName} :

You have cancelled your Tour at {$schoolName} that was scheduled at {$dateTime}.

Thank you

{$schoolName}
{/if}
{/if}
