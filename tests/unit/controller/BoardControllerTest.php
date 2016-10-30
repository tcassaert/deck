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

namespace OCA\Deck\Controller;

use OCA\Deck\Db\Acl;

class BoardControllerTest extends \PHPUnit_Framework_TestCase {

	private $controller;
	private $request;
	private $userManager;
	private $groupManager;
	private $boardService;
	private $permissionService;
	private $userId = 'user';

	public function setUp() {
		$this->l10n = $this->request = $this->getMockBuilder(
			'\OCP\IL10n')
			->disableOriginalConstructor()
			->getMock();
		$this->request = $this->getMockBuilder(
			'\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->userManager = $this->getMockBuilder(
			'\OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager = $this->getMockBuilder(
			'\OCP\IGroupManager')
			->disableOriginalConstructor()
			->getMock();
		$this->boardService = $this->getMockBuilder(
			'\OCA\Deck\Service\BoardService')
			->disableOriginalConstructor()
			->getMock();
		$this->permissionService = $this->getMockBuilder(
			'\OCA\Deck\Service\PermissionService')
			->disableOriginalConstructor()
			->getMock();

		$this->groupManager->method('getUserGroupIds')
			->willReturn(['admin', 'group1', 'group2']);
		$this->userManager->method('get')
			->with($this->userId)
			->willReturn('user');

		$this->controller = new BoardController(
			'deck',
			$this->request,
			$this->userManager,
			$this->groupManager,
			$this->boardService,
			$this->permissionService,
			$this->userId
		);
	}


	public function testIndex() {
		$this->boardService->expects($this->once())
			->method('findAll')
			->willReturn([1, 2, 3]);

		$actual = $this->controller->index();
		$this->assertEquals([1, 2, 3], $actual);
	}

	public function testRead() {
		$this->boardService->expects($this->once())
			->method('find')
			->with(123)
			->willReturn(1);
		$this->assertEquals(1, $this->controller->read(123));
	}

	public function testCreate() {
		$this->boardService->expects($this->once())
			->method('create')
			->with(1, 'user', 3)
			->willReturn(1);
		$this->assertEquals(1, $this->controller->create(1, 3));
	}

	public function testUpdate() {
		$this->boardService->expects($this->once())
			->method('update')
			->with(1, 2, 3)
			->willReturn(1);
		$this->assertEquals(1, $this->controller->update(1, 2, 3));
	}

	public function testDelete() {
		$this->boardService->expects($this->once())
			->method('delete')
			->with(123)
			->willReturn(1);
		$this->assertEquals(1, $this->controller->delete(123));
	}

	public function testGetUserPermissions() {
		$board = $this->getMockBuilder(\OCA\Deck\Db\Board::class)
			->disableOriginalConstructor()
			->setMethods(['getOwner'])
			->getMock();
		$this->boardService->expects($this->once())
			->method('find')
			->with(123)
			->willReturn($board);
		$board->expects($this->once())
			->method('getOwner')
			->willReturn('user');
		$expected = [
			'PERMISSION_READ' => true,
			'PERMISSION_EDIT' => true,
			'PERMISSION_MANAGE' => true,
			'PERMISSION_SHARE' => true,
		];
		$this->assertEquals($expected, $this->controller->getUserPermissions(123));
	}

	public function testGetUserPermissionsNotOwner() {
		$board = $this->getMockBuilder(\OCA\Deck\Db\Board::class)
			->disableOriginalConstructor()
			->setMethods(['getOwner'])
			->getMock();
		$this->boardService->expects($this->once())
			->method('find')
			->with(123)
			->willReturn($board);
		$board->expects($this->once())
			->method('getOwner')
			->willReturn('someoneelse');
		$this->boardService->expects($this->exactly(4))
			->method('getPermission')
			->withConsecutive([123, 'user', Acl::PERMISSION_READ])
			->will($this->onConsecutiveCalls(1, 2, 3, 4));
		$expected = [
			'PERMISSION_READ' => 1,
			'PERMISSION_EDIT' => 2,
			'PERMISSION_MANAGE' => 3,
			'PERMISSION_SHARE' => 4,
		];
		$this->assertEquals($expected, $this->controller->getUserPermissions(123));
	}

	public function testAddAcl() {
		$this->boardService->expects($this->once())
			->method('addAcl')
			->with(1, 2, 3, 4, 5, 6)
			->willReturn(1);
		$this->assertEquals(1, $this->controller->addAcl(1, 2, 3, 4, 5, 6));
	}

	public function testUpdateAcl() {
		$this->boardService->expects($this->once())
			->method('updateAcl')
			->with(1, 2, 3, 4)
			->willReturn(1);
		$this->assertEquals(1, $this->controller->updateAcl(1, 2, 3, 4));
	}

	public function testDeleteAcl() {
		$this->boardService->expects($this->once())
			->method('deleteAcl')
			->with(1)
			->willReturn(1);
		$this->assertEquals(1, $this->controller->deleteAcl(1));
	}
}
