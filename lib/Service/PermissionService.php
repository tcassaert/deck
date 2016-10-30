<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Deck\Service;

use OCA\Deck\Db\Acl;
use OCA\Deck\Db\AclMapper;
use \OCA\Deck\Db\BoardMapper;
use OCP\IGroupManager;
use OCP\ILogger;



class PermissionService {

	private $boardMapper;
	private $aclMapper;
	private $logger;
	private $userId;

	public function __construct(
		ILogger $logger,
		AclMapper $aclMapper,
		BoardMapper $boardMapper,
		IGroupManager $groupManager,
		$userId
	) {
		$this->aclMapper = $aclMapper;
		$this->boardMapper = $boardMapper;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->userId = $userId;
	}

	/**
	 * Get current user permissions for a board
	 *
	 * @param $boardId
	 * @return bool|array
	 */
	public function getPermissions($boardId) {
		$owner = $this->userIsBoardOwner($boardId);
		$acls = $this->aclMapper->findAll($boardId);
		return [
			Acl::PERMISSION_READ => $owner || $this->userCan($acls, Acl::PERMISSION_READ),
			Acl::PERMISSION_EDIT => $owner || $this->userCan($acls, Acl::PERMISSION_READ),
			Acl::PERMISSION_MANAGE => $owner || $this->userCan($acls, Acl::PERMISSION_MANAGE),
			Acl::PERMISSION_SHARE => $owner || $this->userCan($acls, Acl::PERMISSION_SHARE),
		];
	}

	/**
	 * Check if the current user has specified permissions on a board
	 *
	 * @param $boardId
	 * @param $permission
	 * @return bool
	 */
	public function getPermission($boardId, $permission) {
		if ($this->userIsBoardOwner($boardId)) {
			return true;
		}
		$acls = $this->aclMapper->findAll($boardId);
		return $this->userCan($acls, $permission);
	}

	/**
	 * @param $boardId
	 * @return bool
	 */
	public function userIsBoardOwner($boardId) {
		$board = $this->boardMapper->find($boardId);
		if ($board && $this->userId === $board->getOwner()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if permission matches the acl rules for current user and groups
	 *
	 * @param Acl[] $acls
	 * @param $permission
	 * @return bool
	 */
	public function userCan($acls, $permission) {
		// check for users
		foreach ($acls as $acl) {
			if ($acl->getType() === "user" && $acl->getParticipant() === $this->userId) {
				return $acl->getPermission($permission);
			}
		}
		// check for groups
		$hasGroupPermission = false;
		foreach ($acls as $acl) {
			if (!$hasGroupPermission && $acl->getType() === "group" && $this->groupManager->isInGroup($this->userId, $acl->getParticipant())) {
				$hasGroupPermission = $acl->getPermission($permission);
			}
		}
		return $hasGroupPermission;
	}
}