{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* this template is used for displaying Online Application Checklist *}

<div>
  <fieldset>
      <legend>Parent Information - {$parentInfo.display_name} </legend>
    <table>
      <tr><td width = "20%">Parent Name</td>
          <td width = "25%">{$parentInfo.display_name}</td>
          <td>&nbsp;</td>
      </tr>
      <tr><td>Email</td>
          <td>{$parentInfo.email.1.email}</td>
          <td>&nbsp;</td>
      </tr>
      <tr><td>Phone</td>
          <td>{$parentInfo.phone.2.phone}</td>
          <td>&nbsp;</td>
      </tr>
	  <tr>
	  <td>Attend Parent Interview (parents only)</td>
         {if $parentInfo.dashboard.interview.activity_date_time}
           <td><strong>{$parentInfo.dashboard.interview.activity_date_time|date_format:"%a, %b %d, %I:%M %p"}</strong></td>
	   {if $parentInfo.dashboard.interview.is_cancel_url }
             <td><a href="{$parentInfo.dashboard.interview_url}">{ts}Cancel Interview{/ts}</a> </td> 
	     {else}
                 <td></td>
		{/if}
         {else} 
           <td><a href="{$parentInfo.dashboard.interview_url}">{ts}Schedule Interview{/ts}</a></td>
           <td>&nbsp;</td>
         {/if}
      </tr>

      <tr>
         {if $parentInfo.dashboard.tour.activity_date_time}
           <td>Your tour is scheduled for</td>
           <td><strong>{$parentInfo.dashboard.tour.activity_date_time|date_format:"%a, %b %d, %I:%M %p"}</strong></td>
	     {if $parentInfo.dashboard.tour.is_cancel_url }
              <td><a href="{$parentInfo.dashboard.tour_url}">{ts}Cancel tour{/ts}</a></td> 
		{else}
                 <td></td>
		{/if}
         {else} 
           <td>&nbsp;</td>
           <td><a href="{$parentInfo.dashboard.tour_url}">{ts}Book a tour of the school{/ts}</a></td>
           <td>&nbsp;</td>
         {/if}
      </tr>
    </table>
  </fieldset>
</div>

{foreach from=$applicants item=applicant key=key}
<div>
  <fieldset>
    <legend>Applicant - {$applicant.display_name}</legend>
  <table>
    <tr><td width = "20%">Submit Application Form</td>
        <td width = "25%"><strong>{ts}on file{/ts}</strong>&nbsp;{if $applicant.app_complete}({ts}complete{/ts}){else}({ts}incomplete{/ts}){/if}</td>
	  {if !$applicant.is_app_frozen || !$applicant.app_complete}
              <td ><a href="{$applicant.app_url}">Click here to visit form</a></td>
		{else}
                   <td></td>
	      {/if}
															
    </tr>
    <tr><td>Application Fee</td>
        {if !$applicant.app_complete}
          <td>{ts}N.A{/ts}</td>
        {else}
          {if !$applicant.payment_reqd}
            <td><strong>{ts}Waived{/ts}</strong></td>
          {else}
            {if $applicant.payment}
              {if $applicant.payment.status eq 'Completed'}
                <td><strong>{ts}Paid{/ts}</strong></td>
              {else}
                <td><strong>{$applicant.payment.status}</strong></td>
              {/if}
            {else}
              <td><a href="{$applicant.payment_url}">Click here to submit payment</a></td>
            {/if}
          {/if}
        {/if}
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>Attend child visit</td>
        {if $applicant.visit.activity_date_time}
        <td><strong>{$applicant.visit.activity_date_time|date_format:"%a, %b %d, %I:%M %p"}</strong></td>
	{if $applicant.visit.is_cancel_url }
           <td><a href="{$applicant.visit_url}">{ts}Cancel child visit{/ts}</a></td> 
	     {else}
              <td></td>
	      {/if}
        {else} 
        <td><a href="{$applicant.visit_url}">{ts}Schedule child visit{/ts}</a></td> 
        <td>&nbsp;</td>
        {/if} 
    </tr>
    <tr>
        <td>School Report</td>
        <td><a href="#">{ts}Click here for instructions{/ts}</a></td>
        <td>&nbsp;</td>
    </tr>
  </table>
  </fieldset>
</div>
{/foreach}

<div><a class="button" href="{crmURL p='civicrm/school/apply/applicant' q='reset=1'}"><span>&raquo;&nbsp;{ts}Add New Applicant{/ts}</span></a></div>
