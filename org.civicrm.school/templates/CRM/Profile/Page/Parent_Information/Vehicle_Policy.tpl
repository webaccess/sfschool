{assign var=eName value="Employee/Volunteer Name"}
{assign var=license_no value="License Number"}
{assign var=birth_date value="Birth Date"}
{assign var=state_issued value="State Issued"}
{assign var=expiry_date value="Expiration Date"}
{assign var=car_color value="Car Make/Color"}
{assign var=insurance value="Insurance Carrier Name"}
{assign var=license_plate value="License Plate"}
{assign var=no_belt_seats value="# of Seats Belts"}
{assign var=policy_no value="Policy Number"}
{assign var=policy_exp value="Policy Expiration"}
{assign var=policy_period value="Policy Period"}
<fieldset>
	<legend>Vehicle Use Agreement</legend>
  <table>
  <tr><td>Employee/Volunteer Name : {$row.$eName}</td>
      <td>License Number : {$row.$license_no}</td>
      <td>Birth Date : {$row.$birth_date}</td>
  </tr>
  <tr><td>State Issued : {$row.$state_issued}</td>
      <td>Expiration Date : {$row.$expiry_date}</td>
  </tr>

  <tr><td><strong>Automobile and Insurance Information:</strong></td></tr>
  <tr><td>Car Make/Color : {$row.$car_color}</td>
      <td>Insurance Carrier Name : {$row.$insurance}</td>
      <td>License Plate : {$row.$license_plate}</td>
  </tr>
  <tr><td># of Seats Belts : {$row.$no_belt_seats}</td>
      <td>Policy Number : {$row.$policy_no}</td>
  </tr>
  <tr><td>Policy Expiration : {$row.$policy_exp}</td>
      <td>Policy Period : {$row.$policy_period}</td>
  </tr>
  </table>
</fieldset>
