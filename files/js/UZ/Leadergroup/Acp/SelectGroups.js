/**
 * Provides the dialog to configure group leader groups.
 * 
 * @author        2016-2022 Zaydowicz
 * @license        GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package        com.uz.wcf.groupLeader
 */
define(['Ajax', 'Language', 'Ui/Dialog'], function(Ajax, Language, UiDialog) {
    "use strict";

    function UZLeadergroupAcpSelectGroups(groupIDs) { this.init(groupIDs); }
    UZLeadergroupAcpSelectGroups.prototype = {
        init: function(groupIDs) {
            this._groupIDs = groupIDs;

            var button = elBySel('.jsGroupSelectButton');
            button.addEventListener(WCF_CLICK_EVENT, this._click.bind(this));
        },

        _click: function(event) {
            event.preventDefault();

            Ajax.api(this, {
                actionName: 'prepareSelectGroup',
                parameters: { 
                    groupIDs: this._groupIDs
                }
            });
        },

        _ajaxSuccess: function(data) {
            switch (data.actionName) {
                case 'selectGroup':
                    // change fields and close dialog
                    var leaderGroupIDs = elById('leaderGroupIDs');
                    var leaderGroupNames = elById('leaderGroupNames');
                    var leader = elById('leader');

                    leaderGroupIDs.value = data.returnValues.groupIDs;
                    leaderGroupNames.value = data.returnValues.groupNames;
                    leader.value = data.returnValues.usernames;

                    UiDialog.close(this);
                    break;

                case 'prepareSelectGroup':
                    this._render(data);
                    break;
            }
        },

        _render: function(data) {
            UiDialog.open(this, data.returnValues.template);

            // button might be hidden
            var submitButton = elBySel('.jsSubmitLeaderGroup');
            if (!submitButton) return;
            submitButton.addEventListener('click', this._submit.bind(this));
        },

        _submit: function() {
            var ids = []; 
            $('#groupIDs :selected').each(function(i, selected){ 
                ids[i] = $(selected).val();
            });
            this._groupIDs = ids.join(',');

            // everything is fine, send
            Ajax.api(this, {
                actionName: 'selectGroup',
                parameters: {
                    groupIDs: this._groupIDs
                }
            });
        },

        _ajaxSetup: function() {
            return {
                data: {
                    className: 'wcf\\data\\user\\group\\LeaderGroupAction'
                }
            };
        },

        _dialogSetup: function() {
            return {
                id: 'selectGroups',
                options: {
                    title: Language.get('wcf.acp.leaderGroup.title')
                },
                source: null
            };
        }
    };

    return UZLeadergroupAcpSelectGroups;
});
