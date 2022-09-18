<?php

/*
 * Copyright by Udo Zaydowicz.
 * Modified by SoftCreatR.dev.
 *
 * License: http://opensource.org/licenses/lgpl-license.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
namespace wcf\data\user\group;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Executes Leader Groups actions.
 */
class LeaderGroupAction extends AbstractDatabaseObjectAction
{
    /**
     * @inheritDoc
     */
    protected $className = UserGroupEditor::class;

    /**
     * @inheritDoc
     */
    protected $permissionsCreate = ['admin.user.canAddGroup'];

    protected $permissionsDelete = ['admin.user.canDeleteGroup'];

    protected $permissionsUpdate = ['admin.user.canEditGroup'];

    /**
     * @inheritDoc
     */
    protected $requireACP = ['create', 'delete', 'update'];

    /**
     * groupIDs of leader groups
     */
    public $groupIDs = [];

    /**
     * Validates the 'prepareSelectGroup' action.
     */
    public function validatePrepareSelectGroup()
    {
        if (!isset($this->parameters['groupIDs'])) {
            throw new PermissionDeniedException();
        }
        $this->groupIDs = $this->parameters['groupIDs'];
    }

    /**
     * Executes the 'prepareSelectGroup' action.
     */
    public function prepareSelectGroup()
    {
        // get groups with type 4, 6, 7 and 9.
        $allowed = [4, 6, 7, 9];
        $groupList = new UserGroupList();
        $groupList->getConditionBuilder()->add('user_group.groupType IN (?)', [$allowed]);
        // must have members
        $groupList->getConditionBuilder()->add("groupID IN (SELECT DISTINCT groupID FROM wcf" . WCF_N . "_user_to_group)");
        $groupList->readObjects();
        $groups = $groupList->getObjects();

        WCF::getTPL()->assign([
            'groups' => $groups,
            'groupIDs' => \explode(',', $this->groupIDs),
        ]);

        return [
            'template' => WCF::getTPL()->fetch('leaderGroupDialog'),
        ];
    }

    /**
     * Validates the 'selectGroup' action.
     */
    public function validateSelectGroup()
    {
        if (!isset($this->parameters['groupIDs'])) {
            throw new PermissionDeniedException();
        }
        $this->groupIDs = $this->parameters['groupIDs'];
    }

    /**
     * Executes the 'selectGroup' action.
     */
    public function selectGroup()
    {
        // none selected
        if (empty($this->groupIDs)) {
            return [
                'selected' => 1,
                'groupIDs' => '',
                'groupNames' => '',
                'usernames' => '',
            ];
        }

        // get groups, names and users
        $groupIDs = \explode(',', $this->groupIDs);
        $groupNames = [];
        $userGroups = new UserGroupList();
        $userGroups->getConditionBuilder()->add('groupID IN (?)', [$groupIDs]);
        $userGroups->readObjects();
        foreach ($userGroups->getObjects() as $group) {
            $groupNames[] = WCF::getLanguage()->get($group->groupName);
        }

        // get group members
        $userIDs = $usernames = [];
        $conditions = new PreparedStatementConditionBuilder();
        $conditions->add("groupID IN (?)", [$groupIDs]);
        $sql = "SELECT    userID
                FROM    wcf" . WCF_N . "_user_to_group
                " . $conditions;
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($conditions->getParameters());
        while ($row = $statement->fetchArray()) {
            $userIDs[] = $row['userID'];
        }
        $userIDs = \array_unique($userIDs);

        if (\count($userIDs)) {
            $conditions = new PreparedStatementConditionBuilder();
            $conditions->add("userID IN (?)", [$userIDs]);
            $sql = "SELECT    username
                    FROM    wcf" . WCF_N . "_user
                    " . $conditions;
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($conditions->getParameters());
            while ($row = $statement->fetchArray()) {
                $usernames[] = $row['username'];
            }
        }

        return [
            'selected' => 1,
            'groupIDs' => $this->groupIDs,
            'groupNames' => (\count($groupNames) ? \implode(', ', $groupNames) : ''),
            'usernames' => (\count($usernames) ? \implode(', ', $usernames) : ''),
        ];
    }
}
