<div id="crm-container">	
  <fieldset><legend>Conference Creation Wizard</legend>
    <table class='report-layout'>
      <tr>
        <td>
          {if $numberOfSlots neq 0}
            {$form.buttons.html}
          {/if}
        </td>
      </tr>
      {if $numberOfSlots neq 0}
        <tr class="odd-row">
          <td class='conference'>&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&nbsp;{$form.advisor_id.label} &ensp; {$form.advisor_id.html}
          </td>
        </tr>
        <tr class="even-row">
          <td>{$form.booking_start_date.label} &ensp; {$form.booking_start_date.html}</td>
        </tr>
        <tr class="odd-row">
          <td>{$form.booking_end_date.label} &ensp;&nbsp; {$form.booking_end_date.html}</td>
        </tr>
        {if ! $multipleDay}
          <tr class="even-row">
            <td>&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;{$form.ptc_date.label} &ensp; {$form.ptc_date.html}</td>
          </tr>
        {/if}
        <tr class="odd-row">
          <td>&ensp;&ensp;&ensp;&ensp;&ensp;&nbsp;{$form.ptc_subject.label} &ensp; {$form.ptc_subject.html}</td>
        </tr>
        <tr class="even-row">
          <td>&ensp;&ensp;&ensp;&ensp;&ensp;{$form.ptc_duration.label} &ensp; {$form.ptc_duration.html}</td>
        </tr>
        {section name="dates" start=1 step=1 loop=$numberOfSlots+1}
        {assign var='datePrefix' value=ptc_date_}
        {assign var='dateName'   value=$datePrefix|cat:"`$smarty.section.dates.index`"}
        {assign var='timeName'   value=$dateName|cat:"_time"}
          <tr class="{cycle values="odd-row,even-row"}">
            <td>&ensp;&ensp;&nbsp;
              {$form.$dateName.label} &ensp;
              {$form.$dateName.html} &ensp;
              {$form.$timeName.html} &ensp; 
              {if ! empty($assigne[dates])}	
	        ({$assigne[dates]})
	      {/if}
           </td>
        </tr>
     {/section}
   {else}
     <tr class='even-row'>
       <td>
         <div class="messages status">
           <div class="icon inform-icon"></div>
             {ts}No Conferences scheduled for this advisor{/ts}
         </div>
       </td>
     </tr>
   {/if}
  <tr class='odd-row'>
    <td>
      {if $numberOfSlots neq 0}
        {$form.buttons.html}
      {else}
        <a href="{$url}" class="button"><span><div class="icon add-icon"></div>{ts}Setup Parent Teacher Conference{/ts}</span></a>
      {/if}
    </td>
  </tr>
 </table>
</fieldset>
</div>

{literal}
<script type="text/javascript">
  cj("document").ready( function() {
    cj( "#ptc_date_display" ).change( function() {
      var displayDate = cj( "#ptc_date_display" ).val();
	for ( var i = 1; i <= 10; i++ ) {
	  cj( "#ptc_date_"+i ).val( displayDate );
      	  cj( "#ptc_date_"+i+"_display" ).val( displayDate );
	}  
     });
  });
</script>
{/literal}
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

{if !$multipleDay}
  {literal}
    <script type="text/javascript">
      for (var i=1; i <= {/literal}{$numberOfSlots}{literal}; i++) {
        //cj('#ptc_date_' + i).hide( );
        cj('label[for="ptc_date_' + i + '_time"]').hide( );
        cj( "#ptc_date_"+i+"_time" ).attr("size", "10" );
      }
    </script>
  {/literal}
{/if}