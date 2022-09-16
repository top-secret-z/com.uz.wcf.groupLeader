<dl>
	<dt><label for="leaderGroupIDs">{lang}wcf.acp.leaderGroup.groups{/lang}</label></dt>
	<dd>
		<input type="text" id="leaderGroupIDs" name="leaderGroupIDs" value="{$leaderGroupIDs}" hidden class="tiny" /> <span class="button small jsGroupSelectButton" style="margin-bottom:5px;">{lang}wcf.acp.leaderGroup.button{/lang}</span> 
		<textarea id="leaderGroupNames" name="leaderGroupNames" rows="2" cols="40" disabled="disabled">{$leaderGroupNames}</textarea>
		<small>{lang}wcf.acp.leaderGroup.groups.description{/lang}</small>
	</dd>
</dl>

<script data-relocate="true">
		require(['Language', 'UZ/Leadergroup/Acp/SelectGroups'], function (Language, UZLeadergroupAcpSelectGroups) {
			Language.addObject({
				'wcf.acp.leaderGroup.title':		'{jslang}wcf.acp.leaderGroup.title{/jslang}',
				'wcf.acp.leaderGroup.multiSelect':	'{jslang}wcf.acp.leaderGroup.multiSelect{/jslang}'
			});
			new UZLeadergroupAcpSelectGroups('{$leaderGroupIDs}');
		});
</script>
