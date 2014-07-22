<strong>Online Application</strong> - {$values.status}
{if $values.applicant.info }
<fieldset>
<legend>Applicant Info</legend>
<table>
{if $values.applicant.info}
<tr>
<td width=20%><strong>First Name</strong></td>
<td width=80%>{$values.applicant.info.first_name}</td>
</tr>
{if $values.applicant.info.middle_name}
<tr>
<td width=20%><strong>Middle Name</strong></td>
<td width=80%>{$values.applicant.info.middle_name}</td>
</tr>
{/if}
<tr>
<td width=20%><strong>Last Name</strong></td>
<td width=80%>{$values.applicant.info.last_name}</td>
</tr>
{if $values.applicant.info.nick_name}
<tr>
<td width=20%><strong>Prefered Name/Nickname</strong></td>
<td width=80%>{$values.applicant.info.nick_name}</td>
</tr>
{/if}
<tr>
<td width=20%><strong>Gender</strong></td>
<td width=80%>{$values.applicant.info.gender_id}</td>
</tr>
{if $values.applicant.info.details}
{foreach from=$values.applicant.info.details key=dontCare item=pValues}

<tr>
  <td width=20%><strong>{$pValues.title}</strong></td>
  <td width=80%>{$pValues.value}</td>
</tr>
{/foreach}
{/if}
  </tr>
  <tr>
   <td width=20%><strong>Date of Birth</strong></td>
   <td width=80%>{$values.applicant.info.birth_date}</td>
  </tr>
{/if}
</table>
</fieldset>
{/if}


{if $values.school.info.details}
<fieldset>
<legend>School Info</legend>
<table>
 {foreach from=$values.school.info.details key=schoolId item=schoolValues}
   <tr>
    <td width=20%><strong>{$schoolValues.title}</strong></td>
    <td width=80%>{$schoolValues.value}</td>
   </tr>
{/foreach}
</table>
</fieldset>
{/if}


<fieldset>
<legend>Family Information</legend>
<table>
{counter  start=0 skip=1 print=false}
{foreach from=$values.family key=pid item=pValues}
<tr>
    <td width=20%><strong>Guardian </strong></td>
    <td width=80%>{$pValues.display_name}
{if $pValues.address_display}
<br/>{$pValues.address_display}
{/if}
{if $pValues.email_display}
<br/><a href="mailto:{$pValues.email_display}">{$pValues.email_display}</a>
{/if}
{if $pValues.phone_display}
<br/>{$pValues.phone_display}
{/if}

</td>
</tr>
{foreach from=$pValues.info.fdetails key=familyId item=familyValues}
<tr>
  <td width=20%><strong>{$familyValues.title}</strong></td>
  <td width=80%>{$familyValues.value}</td>
</tr>
{/foreach}
{/foreach}
</table>
</fieldset>


{if $values.otherchildren.info.cdetails}
<fieldset>
<legend>Other Children Info</legend>
<table>
{foreach from=$values.otherchildren.info.cdetails key=childId item=childValues}
<tr>
  <td width=20%><strong>{$childValues.title}</strong></td>
  <td width=80%>{$childValues.value}</td>
</tr>
{/foreach}
 </table>
 </fieldset>
{/if}


{if $values.additional.info.adddetails}
<fieldset>
<legend>Additional Info</legend>
<table>
{foreach from=$values.additional.info.adddetails key=additionalId item=additionalValues}

<tr>
  <td width=20%><strong>{$additionalValues.title}</strong></td>
  <td width=80%>{$additionalValues.value}</td>
</tr>
{/foreach}
</table>
</fieldset>
{/if}

