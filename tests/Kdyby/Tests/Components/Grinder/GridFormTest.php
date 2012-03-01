<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Tests\Components\Grinder;

use Kdyby;
use Kdyby\Components\Grinder\GridForm;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class GridFormTest extends Kdyby\Tests\OrmTestCase
{

	public function testRecordIds()
	{
		$form = new GridForm($this->mockDoctrine());

		$grid = $this->mockGrid('getItemsPerPage');
		$grid->expects($this->once())
			->method('getItemsPerPage')
			->will($this->returnValue(20));
		$form->setParent($grid, 'form');

		$form->setRecordIds($ids = range(1, 40, 2));
		$this->assertEquals($ids, $form->getRecordIds());
	}



	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|\Kdyby\Doctrine\Registry
	 */
	private function mockDoctrine()
	{
		return $this->getMockBuilder('Kdyby\Doctrine\Registry')
			->disableOriginalConstructor()
			->getMock();
	}



	/**
	 * @param array|string $methods
	 * @param \Kdyby\Doctrine\Registry $doctrine
	 * @return \PHPUnit_Framework_MockObject_MockObject|\Kdyby\Components\Grinder\Grid
	 */
	private function mockGrid($methods = array(), Kdyby\Doctrine\Registry $doctrine = NULL)
	{
		$doctrine = $doctrine ?: $this->mockDoctrine();
		$qb = $this->getMockBuilder('Kdyby\Doctrine\QueryBuilder')
			->disableOriginalConstructor()
			->getMock();

		return $this->getMock('Kdyby\Components\Grinder\Grid', (array)$methods, array($qb, $doctrine));
	}

}
