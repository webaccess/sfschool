{foreach from=$fields item=field key=fieldName}
{assign var=n value=$field.name}
{if $n}

{if $field.title eq 'Employee/Volunteer Name'}
  {assign var=vehicle_employee_volunteer_name value=$field.name}
{/if}
{if $field.title eq 'License Number'}
  {assign var=vehicle_license_number value=$field.name}
{/if}
{if $field.title eq 'Birth Date'}
  {assign var=vehicle_birth_date value=$field.name}
{/if}
{if $field.title eq 'State Issued'}
  {assign var=vehicle_state_issued value=$field.name}
{/if}
{if $field.title eq 'Expiration Date'}
  {assign var=vehicle_expiration_date value=$field.name}
{/if}
{if $field.title eq 'Car Make/Color'}
  {assign var=vehicle_car_make_color value=$field.name}
{/if}
{if $field.title eq 'Insurance Carrier Name'}
  {assign var=vehicle_insurance_carrier_name value=$field.name}
{/if}
{if $field.title eq 'License Plate'}
  {assign var=vehicle_license_plate value=$field.name}
{/if}
{if $field.title eq '# of Seats Belts'}
  {assign var=vehicle_no_of_seats_belts value=$field.name}
{/if}
{if $field.title eq 'Policy Number'}
  {assign var=vehicle_policy_number value=$field.name}
{/if}
{if $field.title eq 'Policy Expiration'}
  {assign var=vehicle_policy_expiration value=$field.name}
{/if}
{if $field.title eq 'Policy Period'}
  {assign var=vehicle_policy_period value=$field.name}
{/if}
{if $field.title eq 'Agreement'}
  {assign var=vehicle_agreement value=$field.name}
{/if}

{/if}
{/foreach}

<div><strong>{ts}Vehicle Use Agreement{/ts}</strong></div>
<div style = "border:1px solid;">
</div>

  <fieldset><legend>{ts}Registration Info{/ts}</legend>
  <table>
  <tr><td>{$form.$vehicle_employee_volunteer_name.label}  : {$form.$vehicle_employee_volunteer_name.html}</br><i><sub>(name as it appears on your drivers license)</sub></i></td>
      <td>{$form.$vehicle_license_number.label} : {$form.$vehicle_license_number.html}</td>
      <td>{$form.$vehicle_state_issued.label} : {$form.$vehicle_state_issued.html}</td>
  </tr> 
  <tr><td>{$form.$vehicle_birth_date.label} : {include file="CRM/common/jcalendar.tpl" elementName=$vehicle_birth_date}</td>
      <td>{$form.$vehicle_expiration_date.label} : {include file="CRM/common/jcalendar.tpl" elementName=$vehicle_expiration_date}</td>
  </tr> 
  </table>
  </fieldset>

  <fieldset><legend>{ts}Insurance Info{/ts}</legend>
  <table>
      <tr><td>{$form.$vehicle_car_make_color.label} : {$form.$vehicle_car_make_color.html}</td> 
          <td>{$form.$vehicle_license_plate.label} : {$form.$vehicle_license_plate.html}</td>
          <td>{$form.$vehicle_no_of_seats_belts.label} : {$form.$vehicle_no_of_seats_belts.html}</td>
      </tr> 
      <tr><td>{$form.$vehicle_insurance_carrier_name.label} : {$form.$vehicle_insurance_carrier_name.html}</td> 
          <td>{$form.$vehicle_policy_number.label} : {$form.$vehicle_policy_number.html}</td>
      </tr>
      <tr><td>{$form.$vehicle_policy_expiration.label} : {$form.$vehicle_policy_expiration.html}</td> 
          <td>{$form.$vehicle_policy_period.label} : {$form.$vehicle_policy_period.html}</td>
      </tr> 
  </table>
  </fieldset>
  <div>
      <b>Using Personal Vehicles for School Business/Field Trips</b><br>
      <p> Authorization to use a personally owned vehicle for school business/field trips is permitted under the following conditions:</p>
      <ul><li>   All passengers must wear seat belts while the vehicle at all times. Children must be in proper car seats per the California Child Passenger Safety Law.</li>
      <li>   Authorized drivers must have the appropriate license to operate their vehicles.</li>
      <li>   Employees and volunteers must provide a copy of their insurance certificates to the school.</li>
      <li>   Employees and volunteers must maintain current proof of insurance and provide a copy of their insurance certificate each time their policy is renewed or updated.</li>
      <li>   Employees and volunteers must notify this school of all vehicle accidents or violations involving vehicles driven on school business.</li>
      <li>   This school is authorized to review the drivers MVR annually.</li>
      <li>   The vehicle owner is responsible for mechanical repairs, tickets, accidents, and violations.</li>
      <li>   Authorized drivers are not allowed to operate vehicles while under the influence of alcohol, drugs, or other medications that could impair their ability to drive safely.</li>
      <li>   Authorized drivers must comply with all school, city, state and federal laws and regulations at all times.</li>
      </ul>
      <p>The San Francisco School requires that employees and/or volunteers do the following when using a mobile phone when driving for school-sponsored activities:
      <ul><li>   Find a safe place to pull off of the road to accept or place your call, even when using hands-free device.</li>
      <li>   If you receive a call while driving, let the call go to the voicemail.</li>
      </ul>
  </div>
  <div><strong>Automobile and Insurance Information:</strong></div>
      <div style = "border:1px solid;">
      </div><br>
      <pr>I understand that it is my responsibility to operate vehicles safely and follow the requirements of the school vehicle safety policy. I hereby certify that: (a) I have proper and sufficient auto insurance coverage, (b) I am fully licensed to drive in the state of California, (c) I have an excellent driving record, (d) the car I will drive is fully and sufficiently insured, is in good working order, and has sufficient age- and weight-appropriate restraints for each passenger.
         <br><br> I authorize The San Francisco School to obtain my MVR. This authorization remains valid as long as I am an employee or volunteer and may only be rescinded in writing.
         <br><br>{$form.$vehicle_agreement.html}
         <br><br><center>The document can be downloaded from <a href="http://sfschool.org/drupal/sites/default/files/families/publications/The_SF_School_Vehicle_Safety_Policy_1.pdf">here</a></center>	
      </pr>
