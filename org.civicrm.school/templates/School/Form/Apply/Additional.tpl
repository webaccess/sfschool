<div id="common-form-controls" class="form-item">
    {include file="School/Form/Apply/Buttons.tpl"}
      <legend>{ts}Additional Information{/ts}</legend>
 <table>
   <tr>
      <td style="padding:1px" width=28%>"Please describe your child's character and personality.<span title="This field is required." class="crm-marker">*</span><br>(limit 1000 characters)"</td><td>{$form.child_character.html}</td>
   </tr>
   <tr>
    <td></td><td width="30px">Characters Left: {$form.counter_child_character.html}</td>
   </tr>
   <tr>
      <td style="padding:1px" width=28%>Describe the educational environment and experience you envision for your child.<span title="This field is required." class="crm-marker">*</span><br>(limit 500 characters)</td>
      <td>{$form.educ_env.html}</td>
  </tr>
  <tr>
     <td></td><td>Characters Left: {$form.counter_educ_env.html}</td>
  </tr>
  <tr>
     <td style="padding:1px" width=28%>{ts}Please describe any professional support or therapy your child has received (e.g., speech or occupational therapy, educational testing, tutoring).{/ts}<span title="This field is required." class="crm-marker">*</span><br>{ts}(limit 500 characters).{/ts}</td><td>{$form.professional_support.html}</td>  
  </tr>
     <td></td><td>Characters Left: {$form.counter_professional_support.html}</td>
  <tr>
  </tr>
  <tr>
    <td style="padding:1px" width=28%>In order to best meet the needs of your child and family, is there any other information that the school should know?<span title="This field is required." class="crm-marker">*</span><br>(limit 500 characters)</td><td>{$form.needs_of_child.html}</td>
  </tr>
  <tr>
    <td></td><td>Characters Left: {$form.counter_needs_of_child.html}</td>
  </tr>
  <tr>
    <td style="padding:1px" width=28%>{$form.about.label}</td><td>{$form.about.html}</td>
  </tr>
  <tr>
    <td style="padding:1px" width=28%>{$form.reference.label}</td><td>{$form.reference.html}</td>
  </tr>
  <tr>
    <td style="padding:1px" width=28%>{$form.financial_aid.label}</td><td>{$form.financial_aid.html}</td>
  </tr>   
  <tr>
    <td colspan = "3">{$form.is_app_frozen.html}&nbsp;Please check only if you are sure that all details are correct and no further editing of application is required. Once this option is checked, application will not be available for further editing.</td>
  </tr>
   						       
</table>
{include file="School/Form/Apply/Buttons.tpl"}
</div>
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
