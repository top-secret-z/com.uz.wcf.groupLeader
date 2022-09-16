<?php
namespace wcf\system\event\listener;
use wcf\acp\form\UserGroupEditForm;
use wcf\data\user\group\UserGroupList;
use wcf\system\WCF;

/**
 * Listen to Group add / edit
 * 
 * @author		2016-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		com.uz.wcf.groupLeader
 */
class LeaderGroupListener implements IParameterizedEventListener {
	/**
	 * instance of UserGroupAddForm
	 */
	protected $eventObj = null;
	
	/**
	 * leader group data
	 */
	protected $leaderGroupIDs = null;
	protected $leaderGroupNames = '';
	
	/**
	 * @see	\wcf\system\event\listener\IParameterizedEventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		$this->eventObj = $eventObj;
		
		$this->$eventName();
	}
	
	/**
	 * Handles the assignVariables event.
	 */
	protected function assignVariables() {
		if (!empty($this->leaderGroupIDs)) {
			$groupIDs = explode(',', $this->leaderGroupIDs);
			$names = [];
			$userGroups = new UserGroupList();
			$userGroups->getConditionBuilder()->add('groupID IN (?)', [$groupIDs]);
			$userGroups->readObjects();
			
			foreach ($userGroups->getObjects() as $group) {
				$names[] = WCF::getLanguage()->get($group->groupName);
			}
			
			$this->leaderGroupNames = implode(', ', $names);
		}
		
		WCF::getTPL()->assign([
				'leaderGroupIDs' => (!empty($this->leaderGroupIDs) ? $this->leaderGroupIDs : ''),
				'leaderGroupNames' => $this->leaderGroupNames
		]);
	}
	
	/**
	 * Handles the readData event (edit only).
	 */
	protected function readData() {
		if (empty($_POST)) {
			if (!empty($this->eventObj->group->leaderGroupID)) {
				$groupIDs = explode(',', $this->eventObj->group->leaderGroupID);
				$names = [];
				$userGroups = new UserGroupList();
				$userGroups->getConditionBuilder()->add('groupID IN (?)', [$groupIDs]);
				$userGroups->readObjects();
				foreach ($userGroups->getObjects() as $group) {
					$names[] = WCF::getLanguage()->get($group->groupName);
				}
				$this->leaderGroupNames = implode(', ', $names);
				$this->leaderGroupIDs = $this->eventObj->group->leaderGroupID;
			}
		}
	}
	
	/**
	 * Handles the readFormParameters event.
	 */
	protected function readFormParameters() {
		if (isset($_POST['leaderGroupIDs'])) $this->leaderGroupIDs = $_POST['leaderGroupIDs'];
	}
	
	/**
	 * Handles the save event.
	 */
	protected function save() {
		$this->eventObj->additionalFields = array_merge($this->eventObj->additionalFields, [
				'leaderGroupID' => $this->leaderGroupIDs ? $this->leaderGroupIDs : null
		]);
		
		if (!$this->eventObj instanceof UserGroupEditForm) {
			$this->leaderGroupIDs = null;
		}
	}
}
