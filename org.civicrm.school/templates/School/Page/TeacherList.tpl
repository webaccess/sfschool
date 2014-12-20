<div>
  <fieldset><legend>Conference Advisor List</legend>
    <table class="crm-info-panel" >
      <tr class="columnHeader">
        <td class='label'>{ts}Teacher{/ts}</td>
        <td></td>
      </tr>
      {foreach from=$row key=teacher_id item=teacher_name}
        <tr class="{cycle values="odd-row,even-row"}">
          <td>{$teacher_id}</td>
          <td><a href="{$teacher_name}">View</a></td>
        </tr>
      {/foreach}
    </table>
  </fieldset>
</div>
<a href="{$url}" class="button"><span><div class="icon add-icon"></div>{ts}Setup Parent Teacher Conference{/ts}</span></a>
