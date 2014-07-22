<div id="common-form-controls" class="form-item">
{include file="School/Form/Apply/Buttons.tpl"}
      <legend>{ts}School Information{/ts}</legend>
      <dl>
        <dt>{$form.current_school.label}</dt><dd>{$form.current_school.html}</dd> 
        <dt>{$form.current_grade.label}</dt><dd>{$form.current_grade.html}</dd>
        <dt>Dates Attended&nbsp;{$form.attended_from.label}<dd>{include file="CRM/common/jcalendar.tpl" elementName=attended_from}
	{$form.attended_to.label}&nbsp;{include file="CRM/common/jcalendar.tpl" elementName=attended_to}</dd>
        <dt>{$form.address.label}</dt><dd>{$form.address.html}</dd>
        <dt>{$form.city.label}</dt><dd>{$form.city.html}</dd>
        <dt>{$form.country_id.label}</dt><dd>{$form.country_id.html}</dd>
        <dt>{$form.state_id.label}</dt><dd>{$form.state_id.html}</dd>
        <dt>{$form.zip.label}</dt><dd>{$form.zip.html}</dd>
        <dt>{$form.phone.label}</dt><dd>{$form.phone.html}</dd>
        <dt>{$form.name_of_head.label}</dt><dd>{$form.name_of_head.html}</dd>
        <dt>{$form.other.label}</dt><dd>{$form.other.html}</dd>
      </dl>
{include file="School/Form/Apply/Buttons.tpl"}  
</div>