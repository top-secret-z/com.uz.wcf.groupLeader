<?php
namespace wcf\data\user\group;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Executes Leader Groups actions.
 * 
 * @author		2016-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.groupLeader
 */
class LeaderGroupAction extends AbstractDatabaseObjectAction {
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
	public function validatePrepareSelectGroup() {
		if (!isset($this->parameters['groupIDs'])) {
			throw new PermissionDeniedException();
		}
		$this->groupIDs = $this->parameters['groupIDs'];
	}
	
	/**
	 * Executes the 'prepareSelectGroup' action.
	 */
	public function prepareSelectGroup() {
		// get groups with type 4, 6, 7 and 9.
		$allowed = [4, 6, 7, 9];
		$groupList = new UserGroupList();
		$groupList->getConditionBuilder()->add('user_group.groupType IN (?)', [$allowed]);
		// must have members
		$groupList->getConditionBuilder()->add("groupID IN (SELECT DISTINCT groupID FROM wcf".WCF_N."_user_to_group)");
		$groupList->readObjects();
		$groups = $groupList->getObjects();
		
		WCF::getTPL()->assign([
				'groups' => $groups,
				'groupIDs' => explode(',', $this->groupIDs)
		]);
		
		return [
				'template' => WCF::getTPL()->fetch('leaderGroupDialog')
		];
	}
	
	/**
	 * Validates the 'selectGroup' action.
	 */
	public function validateSelectGroup() {
		if (!isset($this->parameters['groupIDs'])) {
			throw new PermissionDeniedException();
		}
		$this->groupIDs = $this->parameters['groupIDs'];
	}
	
	/**
	 * Executes the 'selectGroup' action.
	 */
	public function selectGroup() {
		// none selected
		if (empty($this->groupIDs)) {
			return [
					'selected' => 1,
					'groupIDs' => '',
					'groupNames' => '',
					'usernames' => ''
			];
		}
		
		// get groups, names and users
		$groupIDs = explode(',', $this->groupIDs);
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
		$sql = "SELECT	userID
				FROM	wcf".WCF_N."_user_to_group
				".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		while ($row = $statement->fetchArray()) {
			$userIDs[] = $row['userID'];
		}
		$userIDs = array_unique($userIDs);
		
		if (count($userIDs)) {
			$conditions = new PreparedStatementConditionBuilder();
			$conditions->add("userID IN (?)", [$userIDs]);
			$sql = "SELECT	username
					FROM	wcf".WCF_N."_user
					".$conditions;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditions->getParameters());
			while ($row = $statement->fetchArray()) {
				$usernames[] = $row['username'];
			}
		}
		
		return [
				'selected' => 1,
				'groupIDs' => $this->groupIDs,
				'groupNames' => (count($groupNames) ? implode(', ', $groupNames) : ''),
				'usernames' => (count($usernames) ? implode(', ', $usernames) : '')
		];
	}
}
