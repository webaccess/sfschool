{* Profile forms when embedded in CMS account create (mode=1) or edit (mode=8) pages *}
{if ! empty( $fields )}
{* wrap in crm-container div so crm styles are used *}
<div id="crm-container" class="crm-container crm-public" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">

    {if $mode eq 8 || $mode eq 1}
        {include file="CRM/Form/body.tpl"}
    {/if}

    {strip}
    {if $help_pre && $action neq 4}
    <div class="messages help">{$help_pre}</div>
    {/if}

    {include file="CRM/common/CMSUser.tpl"}

    {if $action eq 2 and $multiRecordFieldListing}
      <h1>{ts}Edit Details{/ts}</h1>
      <div class="crm-submit-buttons" style='float:right'>
      {include file="CRM/common/formButtons.tpl"}{if $isDuplicate}<span class="crm-button">{$form._qf_Edit_upload_duplicate.html}</span>{/if}
      </div>
    {/if}

    {assign var=zeroField value="Initial Non Existent Fieldset"}
    {assign var=fieldset  value=$zeroField}
    {foreach from=$fields item=field key=fieldName}
    {assign var=n value=$field.name}
    {if $form.$n}

    {if $field.groupTitle != $fieldset}
        {if $fieldset != $zeroField}
           </table>
           {if $groupHelpPost}
              <div class="messages help">{$groupHelpPost}</div>
           {/if}

           {if $mode eq 8}
              </fieldset>
           {else}
              </fieldset>
              </div>
           {/if}
        {/if}

        {if $mode eq 8}
            <fieldset>
        {else}
	   {if $context neq 'dialog'}
              <div id="profilewrap{$field.group_id}">
              <fieldset><legend>{ts}{$field.groupTitle}{/ts}</legend>
           {else}
              <div>
	      <fieldset><legend>{ts}{$field.groupTitle}{/ts}</legend>
	   {/if}
        {/if}
        {assign var=fieldset  value=`$field.groupTitle`}
        {assign var=groupHelpPost  value=`$field.groupHelpPost`}
        {if $field.groupHelpPre}
            <div class="messages help">{$field.groupHelpPre}</div>
        {/if}
        <table class="form-layout-compressed">
     {/if}

    {if $field.options_per_line}
	<tr id="editrow-{$n}">
        <td class="option-label">{$form.$n.label}</td>
        <td class="edit-value">
	    {assign var="count" value="1"}
        {strip}
        <table class="form-layout-compressed">
        <tr>
          {* sort by fails for option per line. Added a variable to iterate through the element array*}
          {assign var="index" value="1"}
          {foreach name=outer key=key item=item from=$form.$n}
          {if $index < 10}
              {assign var="index" value=`$index+1`}
          {else}
              <td class="labels font-light">{$form.$n.$key.html}</td>
              {if $count == $field.options_per_line}
                  </tr>
                  <tr>
                   {assign var="count" value="1"}
              {else}
        	   {assign var="count" value=`$count+1`}
              {/if}
          {/if}
          {/foreach}
        </tr>
        </table>
	{if $field.html_type eq 'Radio' and $form.formName eq 'Edit'}
            &nbsp;&nbsp;(&nbsp;<a href="#" title="unselect" onclick="unselectRadio('{$n}', '{$form.formName}'); return false;">{ts}unselect{/ts}</a>&nbsp;)
	{/if}
        {/strip}
        </td>
    </tr>
	{else}
        <tr id="editrow-{$n}">
           {if $n|substr:0:7 neq "custom_" AND $n|substr:0:8 neq "vehicle_"}
              <td class="label">{$form.$n.label}</td>
           {/if}
           <td class="edit-value">
           {if $n|substr:0:3 eq 'im-'}
             {assign var="provider" value=$n|cat:"-provider_id"}
             {$form.$provider.html}&nbsp;
           {/if}

           {if $n eq 'greeting_type'}
               <table class="form-layout-compressed">
                  <tr>
                     <td>{$form.$n.html} </td>
                     <td id="customGreeting">
                     {$form.custom_greeting.label}&nbsp;&nbsp;&nbsp;{$form.custom_greeting.html|crmReplace:class:big}</td>
                  </tr>
               </table>
            {elseif $n eq 'group' && $form.group}
                <table id="selector" class="selector" style="width:auto;">
                    <tr><td>{$form.$n.html}{* quickform add closing </td> </tr>*}
                </table>
           {else}
               {if $n|substr:0:7 neq 'custom_' AND $n|substr:0:8 neq "vehicle_"}
                   {$form.$n.html}
               {/if}
               {if ($n eq 'gender') or ($field.html_type eq 'Radio' and $form.formName eq 'Edit')}
                       &nbsp;&nbsp;(&nbsp;<a href="#" title="unselect" onclick="unselectRadio('{$n}', '{$form.formName}'); return false;">{ts}unselect{/ts}</a>&nbsp;)
               {elseif $field.data_type eq 'Date' and $n|substr:0:7 neq 'custom_'}
	            {if $element.skip_calendar NEQ true }
                    <span>
                       {include file="CRM/common/jcalendar.tpl" elementName=$n}
		    </span>
		    {/if}
               {/if}
           {/if}
           </td>
        </tr>
        {if $form.$n.type eq 'file'}
	      <tr><td class="label"></td><td>{$customFiles.$n.displayURL}</td></tr>
	      <tr><td class="label"></td><td>{$customFiles.$n.deleteURL}</td></tr>
        {/if}
	{/if}

    {* Show explanatory text for field if not in 'view' mode *}
    {if $field.help_post && $action neq 4 && $form.$n.html}
        <tr id="helprow-{$n}"><td>&nbsp;</td><td class="description">{$field.help_post}</td></tr>
    {/if}

    {/if}
    {/foreach}
    </table>

{if $occupiedSlots}
<fieldset>
<legend>Scheduled Conferences</legend>
<table class="form-layout-compressed">
  <tr>
     <th>Student Name</th>
     <th>Time</th>
     <th>Cancel?</th>
 </tr>
{foreach from=$occupiedSlots item=slot}
 <tr>
   {assign var=cb_name value=$slot.cb_name}
   <td><a href="{crmURL p='civicrm/profile/view' q="reset=1&gid=`$studentProfileID`&id=`$slot.id`"}">{$slot.name}</a></td>
   <td>{$slot.time}</td>
   <td>{$form.$cb_name.html}</td>
 </tr>
{/foreach}
</table>
{/if}

{if $emptySlots}
<fieldset>
<legend>Available Conference Slots</legend>
<table class="form-layout-compressed">
  <tr>
     <th>Time</th>
     <th>Schedule</th>
     <th>Delete?</th>
 </tr>
{foreach from=$emptySlots item=slot}
 <tr>
   {assign var=cb_name value=$slot.cb_name}
   {assign var=select_name value=$slot.select_name}
   <td>{$slot.time}</td>
   <td>{$form.$select_name.html}</td>
   <td>{$form.$cb_name.html}</td>
 </tr>
{/foreach}
</table>
</fieldset>
{/if}

{if $occupiedSlots or $emptySlots}
<fieldset>
<legend>Add a New Conference Slot</legend>
<table class="form-layout-compressed">
  <tr>
    <th>Date</th>
    <th>Duration</th>
    <th>Contact</th>
  </tr>
  <tr>
     <td>{include file="CRM/common/jcalendar.tpl" elementName=slot_date}</td>
     <td>{$form.slot_duration.html}</td>
     <td>{$form.slot_contact_id.html}</td>
  </tr>
</table>
</fieldset>
{/if}

<table class="form-layout-compressed">
        {if $addToGroupId}
	        <tr><td class="label">{$form.group[$addToGroupId].label}</td><td>{$form.group[$addToGroupId].html}</td></tr>
        {/if}

    </table>

    {if $isCaptcha && ( $mode eq 8 || $mode eq 4 || $mode eq 1 ) }
        {include file='CRM/common/ReCAPTCHA.tpl'}
        <script type="text/javascript">cj('.recaptcha_label').attr('width', '140px');</script>
    {/if}

    {if $field.groupHelpPost}
        <div class="messages help">{$field.groupHelpPost}</div>
    {/if}

    {if $mode eq 8}
        </fieldset>
    {else}
        </fieldset>
        </div>
    {/if}

{include file="CRM/Profile/Form/Parent_Information/Vehicle_Policy.tpl"}

{if $mode eq 4 OR $mode eq 8}
<div class="crm-submit-buttons" style='{$floatStyle}'>
     {$form.buttons.html}{if $isDuplicate}&nbsp;&nbsp;{$form._qf_Edit_upload_duplicate.html}{/if}
</div>
{/if}
     {if $help_post && $action neq 4}<br /><div class="messages help">{$help_post}</div>{/if}
    {/strip}

</div> {* end crm-container div *}

<script type="text/javascript">
  {if $drupalCms}
  {literal}
    if ( document.getElementsByName("cms_create_account")[0].checked ) {
       cj('#details').show();
    } else {
       cj('#details').hide();
    }
  {/literal}
  {/if}
</script>
{/if} {* fields array is not empty *}
{if $multiRecordFieldListing and empty($fields)}
  {include file="CRM/Profile/Page/MultipleRecordFieldsListing.tpl" showListing=true}
{/if}
{if $drupalCms}
{include file="CRM/common/showHideByFieldValue.tpl"
trigger_field_id    ="create_account"
trigger_value       =""
target_element_id   ="details"
target_element_type ="block"
field_type          ="radio"
invert              = 0
}
{/if}

{if $form.greeting_type}
  {literal}
    <script type="text/javascript">
      window.onload = function() {
        showGreeting();
      }
  {/literal}
    </script>
{/if}
{literal}
<script type="text/javascript">
    function showGreeting() {
       if( document.getElementById("greeting_type").value == 4 ) {
            cj('#customGreeting').show();
       } else {
            cj('#customGreeting').hide();
       }
    }
CRM.$(function($) {
	cj('#selector tr:even').addClass('odd-row ');
	cj('#selector tr:odd ').addClass('even-row');
});
</script>
{/literal}

{include file="School/common/footer.tpl"}
