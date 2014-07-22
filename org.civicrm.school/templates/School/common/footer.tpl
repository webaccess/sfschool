{crmDBTpl context='school family config' name='footer text' var='footer'}
<div class="footer" id="civicrm-footer">
{if !empty($footer) }
  {$footer}
{else}
  If you want to change any of your (or your child's) Personal Information (name, email, phone), please send an email to <a href="mailto:update@sfschool.org">update@sfschool.org</a>
{/if}
</div>