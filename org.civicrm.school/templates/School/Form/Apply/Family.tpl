{include file="School/Form/Apply/Buttons.tpl"}
<legend>{ts}Family Information{/ts}</legend>
{section name="parentNumber" start=1 step=1 loop=3}
{assign  var='parentNum'     value=$smarty.section.parentNumber.index}
{if $parentNum is odd}
 <table>
     <tr>
 {/if}
      <td style="padding:1px; width:45%" >
        <table style="border:1px solid #999999;">
          <tr><th colspan=2>Guardian {$parentNum}</th></tr>
               <tr>
                 <td style="padding:2px" width=30%>{$form.contact.$parentNum.prefix_id.label}</td><td>{$form.contact.$parentNum.prefix_id.html} </td> 
		 </tr>
		 <tr>
                 <td style="padding:2px" width=30%>{$form.contact.$parentNum.first_name.label}</td><td>{$form.contact.$parentNum.first_name.html}&nbsp;{$form.contact.$parentNum.last_name.html}</td>
		 </tr>
		 <tr>
		 <td style="padding:2px" width=30%>{$form.contact.$parentNum.email.1.email.label}</td><td>{$form.contact.$parentNum.email.1.email.html}</td>
		 </tr>
		 <tr>
                 <td style="padding:2px" width=30%>{$form.contact.$parentNum.relationship_name.label}</td><td>{$form.contact.$parentNum.relationship_name.html}</td>                
		 </tr>
		 <tr>
		 <td style="padding:2px" width=30%>{$form.contact.$parentNum.phone.1.phone.label}</td><td>{$form.contact.$parentNum.phone.1.phone.html}</td>
		 </tr>
		 <tr>
		 <td style="padding:2px" width=30%>{$form.contact.$parentNum.phone.2.phone.label}</td><td>{$form.contact.$parentNum.phone.2.phone.html}</td>
		 </tr>
		 <tr>
		 <td style="padding:2px" width=30%>{$form.address.$parentNum.street_address.label}</td><td>{$form.address.$parentNum.street_address.html}</td>
		 </tr>
		 <tr>
		 <td style="padding:2px" width=30%>{$form.address.$parentNum.country_id.label}</td><td>{$form.address.$parentNum.country_id.html}</td>
		 </tr>
		 <tr>
		 <td style="padding:2px" width=30%>{$form.address.$parentNum.state_province_id.label}</td><td>{$form.address.$parentNum.state_province_id.html}</td>
		 </tr>
		 <tr>
		 <td style="padding:2px" width=30%>{$form.address.$parentNum.city.label}</td><td>{$form.address.$parentNum.city.html}</td>
		 </tr>
		 <tr>
		 <td style="padding:2px" width=30%>{$form.address.$parentNum.postal_code.label}</td><td>{$form.address.$parentNum.postal_code.html}</td>
		 </tr>
		 <tr>
		 <td style="padding:2px" width=30%>{$form.contact.$parentNum.employer.label}</td><td>{$form.contact.$parentNum.employer.html}</td>
		 </tr>
		 <tr>
		 <td style="padding:2px" width=30%>{$form.contact.$parentNum.occupation.label}</td><td>{$form.contact.$parentNum.occupation.html}</td>
		 </tr>
		 <tr>
		 <td style="padding:2px" width=30%>{$form.contact.$parentNum.position.label}</td><td>{$form.contact.$parentNum.position.html}</td>
		 </tr>
		 <tr>
{assign  var='addressNum' value=$parentNum+2}
		 <td style="padding:2px" width=30%>Business address</td><td>{$form.address.$addressNum.street_address.html}</td>
		 </tr>
		 <tr>
		 <td style="padding:2px" width=30%>{$form.contact.$parentNum.business_phone.label}</td><td>{$form.contact.$parentNum.business_phone.html}</td>
		 </tr>
		 </table>
		 </td>

{if $parentNum is even}
</tr>
</table>
{/if}
{/section}

<table style="border:1px solid #999999;">
      {foreach key=key item=item from=$fieldNames}
               <tr>
                  <td width="50%">{$form.$item.label}</td><td>{$form.$item.html}</td>
               </tr>
               {if $item eq 'circumstances'}
               <tr>
                 <td></td>
                 <td>Characters Left: {$form.counter_circumstances.html}</td>
               </tr>
               {/if}
      {/foreach}   
</table>
{include file="School/Form/Apply/Buttons.tpl"}

{literal}
 <script type="text/javascript">
    function wordcount(field_id,count){
     var text_area       = document.getElementById(field_id);
     var count_element   = document.getElementById("counter_" + field_id);
     var text_area_value = text_area.value;
     var length          = text_area_value.length;
    
      if (length > count){ 
          // if too long...trim it!
          text_area_value = text_area_value.substring(0, count);
          document.getElementById(field_id).value = text_area_value;
          alert("You have reached the maximum limit");
          return false;
      } else {
           // otherwise, update 'characters left' counter
           document.getElementById("counter_" + field_id).value = count - length;
      }
 }
 </script>
{/literal}

