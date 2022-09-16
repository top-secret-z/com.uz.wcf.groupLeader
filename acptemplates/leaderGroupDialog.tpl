<section class="section">
	<h2 class="sectionTitle">{lang}wcf.acp.leaderGroup.groups.selection{/lang}</h2>
	
{if $groups|count}
	<p class="sectionDescription">{lang}wcf.acp.leaderGroup.multiSelect{/lang}</p>
	
	<dl>
		<dt><label for="groupIDs">{lang}wcf.acp.leaderGroup.groups{/lang}</label></dt>
		<dd>
			<select id="groupIDs" name="groupIDs[]" multiple="multiple" size="10">
				{foreach from=$groups item=group}
					<option value="{$group->groupID}"{if $group->groupID|in_array:$groupIDs} selected="selected"{/if}>{$group->groupName|language}</option>
				{/foreach}
			</select>
			<small>{lang}wcf.acp.leaderGroup.multiSelect.strg{/lang}</small>
		</dd>
	</dl>
</section>

<div class="formSubmit">
	<button class="jsSubmitLeaderGroup buttonPrimary" accesskey="s">{lang}wcf.global.button.submit{/lang}</button>
	<input type="hidden" name="groupIDs" value="{$groupIDs}" />
</div>
{else}
	<p>{lang}wcf.acp.leaderGroup.groups.none{/lang}</p>
</section>
{/if}
