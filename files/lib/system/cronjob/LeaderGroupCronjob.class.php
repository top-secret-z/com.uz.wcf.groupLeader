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
namespace wcf\system\cronjob;

use wcf\data\cronjob\Cronjob;
use wcf\data\user\group\UserGroupList;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Leader group cronjob.
 */
class LeaderGroupCronjob extends AbstractCronjob
{
    /**
     * @inheritDoc
     */
    public function execute(Cronjob $cronjob)
    {
        parent::execute($cronjob);

        // get user groups with leader group
        $groupList = new UserGroupList();
        $groupList->readObjects();
        $groups = $groupList->getObjects();

        // allowed group types
        $allowed = [4, 6, 7, 9];

        if (\count($groups)) {
            foreach ($groups as $group) {
                // check configured groups
                if ($group->leaderGroupID === null) {
                    continue;
                }

                // update group string if groups were deleted / changed to other type
                $leaderGroupIDs = \explode(',', $group->leaderGroupID);
                $groupIDs = [];
                $missing = false;
                $string = $group->leaderGroupID;
                foreach ($leaderGroupIDs as $id) {
                    if (isset($groups[$id]) && \in_array($groups[$id]->groupType, $allowed)) {
                        $groupIDs[] = $id;
                    } else {
                        $missing = true;
                    }
                }

                if ($missing) {
                    if (\count($groupIDs)) {
                        $string = \implode(',', $groupIDs);
                    } else {
                        $string = null;
                    }

                    $sql = "UPDATE    wcf" . WCF_N . "_user_group
                            SET     leaderGroupID = ?
                            WHERE    groupID = ?";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute([$string, $group->groupID]);
                }

                // get present leaders
                $leaderIDs = [];
                $sql = "SELECT    leaderID
                        FROM    wcf" . WCF_N . "_user_group_leader
                        WHERE    groupID = ?";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$group->groupID]);
                while ($row = $statement->fetchArray()) {
                    $leaderIDs[] = $row['leaderID'];
                }

                // delete all leaders if no leader group left
                if (empty($string)) {
                    $sql = "DELETE FROM    wcf" . WCF_N . "_user_group_leader
                            WHERE         groupID = ?";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute([$group->groupID]);
                } else {
                    // get users in leader groups
                    $userIDs = [];
                    $groupIDs = \explode(',', $string);
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

                    // get leaders not in group and users in group who are not leaders
                    $deletes = \array_diff($leaderIDs, $userIDs);
                    $adds = \array_diff($userIDs, $leaderIDs);

                    if (\count($deletes)) {
                        $conditions = new PreparedStatementConditionBuilder();
                        $conditions->add("leaderID IN (?)", [$deletes]);
                        $conditions->add("groupID = ?", [$group->groupID]);
                        $sql = "DELETE FROM wcf" . WCF_N . "_user_group_leader
                                " . $conditions;
                        $statement = WCF::getDB()->prepareStatement($sql);
                        $statement->execute($conditions->getParameters());
                    }

                    if (\count($adds)) {
                        $sql = "INSERT INTO    wcf" . WCF_N . "_user_group_leader
                                (groupID, leaderID)
                                VALUES        (?, ?)";
                        $statement = WCF::getDB()->prepareStatement($sql);
                        WCF::getDB()->beginTransaction();
                        foreach ($adds as $id) {
                            $statement->execute([$group->groupID, $id]);
                        }
                        WCF::getDB()->commitTransaction();
                    }
                }
            }
        }
    }
}
