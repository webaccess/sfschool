<div class="form-item">	
<fieldset>
<legend>Scheduling Slots Wizard</legend>
<dl>
    <dt></dt>
    <dd>{$form.buttons.html}<dd/>
</dl>
<dl>
<dt>{$form.activity_id.label}</dt><dd>{$form.activity_id.html}&nbsp;&nbsp;&nbsp;
<a href="{crmURL p='civicrm/school/apply/schedule' q="reset=1&multipleDay=true&perSlot=true"}">{ts}Add Multiple Days and Slots{/ts}</a>
</dd>
{if ! $multipleDay}
<dt>{$form.sch_date.label}</dt><dd>{include file="CRM/common/jcalendar.tpl" elementName=sch_date}</dd>
{/if}
<dt>{$form.sch_duration.label}</dt><dd>{$form.sch_duration.html}</dd>
{section name="dates" start=1 step=1 loop=$numberOfSlots}
{assign var='datePrefix' value=sch_date_}
{assign var='dateName'   value=$datePrefix|cat:"`$smarty.section.dates.index`"}
<dt>{$form.$dateName.label}<dd>{include file="CRM/common/jcalendar.tpl" elementName=`$dateName`}
{assign var=activityslot value="activity_slot_"|cat:"`$smarty.section.dates.index`" }
&nbsp;&nbsp;{$form.$activityslot.label}&nbsp;&nbsp;{$form.$activityslot.html}</dd></dt>
{/section}
</dl>
<dl>
    <dt></dt>
    <dd>{$form.buttons.html}<dd/>
</dl>
</fieldset>
</div>

{if $summary}
<div>
<table class="selector">
  <tr class="columnheader">
     <th>Name</th>
     <th>Total Blocks</th>
{if $showDetails}
     <th>Details</th>
{/if}
  </tr>
{foreach from=$summary item=row}
{if $row.blockCharge > 0 OR $showDetails}
  <tr class="{cycle values="odd-row,even-row"}">
    <td>{$row.name}</td>
    <td>{$row.blockCharge}</td>
{if $showDetails}
    <td>
<table>
{foreach from=$row.details item=detail}
<tr>
       <td>{$detail.charge}</td>
       <td>{$detail.class}</td>
       <td>{$detail.signout}{if $detail.pickup} by {$detail.pickup}{/if}</td>
       <td>{$detail.message}</td>
</tr>
{/foreach}
</table>
    </td>
{/if}
  </tr>
{/if}
{/foreach}
</table>
</div>
{/if}

{if ! $multipleDay}
{literal}
<script type="text/javascript">
    for (var i=1; i <= {/literal}{$numberOfSlots}{literal}; i++) {
        cj('#sch_date_' + i).hide( );
        cj('label[for="sch_date_' + i + '_time"]').hide( );
    }
</script>
{/literal}
{/if}