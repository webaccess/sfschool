<div id="common-form-controls" class="form-item">
{include file="School/Form/Apply/Buttons.tpl"}
      <legend>{ts}Applicant Information{/ts}</legend>
      <dl>
        <dt>{$form.first_name.label}</dt><dd>{$form.first_name.html}</dd>
        <dt>{$form.middle_name.label}</dt><dd>{$form.middle_name.html}</dd>
        <dt>{$form.last_name.label}</dt><dd>{$form.last_name.html}</dd>
        <dt>{$form.nick_name.label}</dt><dd>{$form.nick_name.html}</dd>
        <dt>{$form.applying_grade.label}</dt><dd>{$form.applying_grade.html}</dd>
        <dt>{$form.year.label}</dt><dd>{$form.year.html}</dd>
        <dt>{$form.birth_date.label}</dt><dd>{include file="CRM/common/jcalendar.tpl" elementName=birth_date}</dd>
        <dt>{$form.gender_id.label}</dt><dd>{$form.gender_id.html}</dd>
      </dl>
{include file="School/Form/Apply/Buttons.tpl"}
</div>


