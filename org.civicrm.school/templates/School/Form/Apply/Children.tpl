<div id="common-form-controls" class="form-item">
{include file="School/Form/Apply/Buttons.tpl"}
      <legend>{ts}Other Information{/ts}</legend>
      <table width="100%">
      {foreach key=key item=item from=$fieldNames}
          <tr>
              {assign var="name"   value=$item[0]}
              {assign var="dob"    value=$item[1]}
              {assign var="school" value=$item[2]}
              {assign var="apply"  value=$item[3]}
              <td width = "25%">{$form.$name.label}{$form.$name.html}</td>
              <td width = "25%">{$form.$dob.label}{include file="CRM/common/jcalendar.tpl" elementName= $dob}</td>
              <td width = "25%">{$form.$school.label}{$form.$school.html}</td>
              <td width = "25%">{$form.$apply.label}{$form.$apply.html}</td>
          </tr>
      {/foreach}   
      </table>
{include file="School/Form/Apply/Buttons.tpl"}
</div>