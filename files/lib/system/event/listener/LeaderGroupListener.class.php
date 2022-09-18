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
namespace wcf\system\event\listener;

use wcf\acp\form\UserGroupEditForm;
use wcf\data\user\group\UserGroupList;
use wcf\system\WCF;

/**
 * Listen to Group add / edit
 */
class LeaderGroupListener implements IParameterizedEventListener
{
    /**
     * instance of UserGroupAddForm
     */
    protected $eventObj;

    /**
     * leader group data
     */
    protected $leaderGroupIDs;

    protected $leaderGroupNames = '';

    /**
     * @see    \wcf\system\event\listener\IParameterizedEventListener::execute()
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        $this->eventObj = $eventObj;

        $this->{$eventName}();
    }

    /**
     * Handles the assignVariables event.
     */
    protected function assignVariables()
    {
        if (!empty($this->leaderGroupIDs)) {
            $groupIDs = \explode(',', $this->leaderGroupIDs);
            $names = [];
            $userGroups = new UserGroupList();
            $userGroups->getConditionBuilder()->add('groupID IN (?)', [$groupIDs]);
            $userGroups->readObjects();

            foreach ($userGroups->getObjects() as $group) {
                $names[] = WCF::getLanguage()->get($group->groupName);
            }

            $this->leaderGroupNames = \implode(', ', $names);
        }

        WCF::getTPL()->assign([
            'leaderGroupIDs' => (!empty($this->leaderGroupIDs) ? $this->leaderGroupIDs : ''),
            'leaderGroupNames' => $this->leaderGroupNames,
        ]);
    }

    /**
     * Handles the readData event (edit only).
     */
    protected function readData()
    {
        if (empty($_POST)) {
            if (!empty($this->eventObj->group->leaderGroupID)) {
                $groupIDs = \explode(',', $this->eventObj->group->leaderGroupID);
                $names = [];
                $userGroups = new UserGroupList();
                $userGroups->getConditionBuilder()->add('groupID IN (?)', [$groupIDs]);
                $userGroups->readObjects();
                foreach ($userGroups->getObjects() as $group) {
                    $names[] = WCF::getLanguage()->get($group->groupName);
                }
                $this->leaderGroupNames = \implode(', ', $names);
                $this->leaderGroupIDs = $this->eventObj->group->leaderGroupID;
            }
        }
    }

    /**
     * Handles the readFormParameters event.
     */
    protected function readFormParameters()
    {
        if (isset($_POST['leaderGroupIDs'])) {
            $this->leaderGroupIDs = $_POST['leaderGroupIDs'];
        }
    }

    /**
     * Handles the save event.
     */
    protected function save()
    {
        $this->eventObj->additionalFields = \array_merge($this->eventObj->additionalFields, [
            'leaderGroupID' => $this->leaderGroupIDs ? $this->leaderGroupIDs : null,
        ]);

        if (!$this->eventObj instanceof UserGroupEditForm) {
            $this->leaderGroupIDs = null;
        }
    }
}
