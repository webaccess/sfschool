{foreach from=$profileGroups item=group}
    <h2>{$group.title}</h2>
    <div id="profilewrap{$groupID}" class="crm-profile-view">
    	 {$group.content}
    </div>
{/foreach}

{include file="School/common/footer.tpl"}
