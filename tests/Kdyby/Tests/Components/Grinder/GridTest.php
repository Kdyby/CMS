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
use Kdyby\Components\Grinder\Grid;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class GridTest extends Kdyby\Tests\OrmTestCase
{

	/** @var \Kdyby\Components\Grinder\Grid */
	private $grid;



	protected function setUp()
	{
		$this->createOrmSandbox(array(
			$entityName = __NAMESPACE__ . '\Fixtures\RootEntity'
		));

		$this->grid = Grid::createFromEntity($this->getDoctrine(), $entityName);
	}



	/**
	 * @return array
	 */
	public function dataAttached_validateSorting()
	{
		return array(
			array(array('name' => 'asc'), array('name' => 'asc')),
			array(array('daddy' => 'desc'), array()),
			array(array('daddy.name' => 'desc'), array('daddy.name' => 'desc')),
		);
	}



	/**
	 * @dataProvider dataAttached_validateSorting
	 *
	 * @param array $sort
	 * @param array $result
	 */
	public function testAttached_validateSorting(array $sort, array $result)
	{
		$this->grid->sort = $sort;
		$this->attachToPresenter($this->grid);
		$this->assertEquals($result, $this->grid->sort);
	}



	public function testAttached_validateSorting_sortingDisabled()
	{
		$this->grid->sort['name'] = 'asc';
		$this->grid->disableSorting = TRUE;
		$this->attachToPresenter($this->grid);
		$this->assertEquals(array(), $this->grid->sort);
	}



	public function testAttached_validateSorting_multiSortingWhenDisabled()
	{
		$this->grid->sort = array('name' => 'asc', 'daddy.name' => 'desc');
		$this->grid->multiSort = FALSE;
		$this->attachToPresenter($this->grid);
		$this->assertEquals(array(), $this->grid->sort);
	}



	public function testAttached_validateSorting_multiSortingWhenAllowed()
	{
		$this->grid->sort = $sort = array('name' => 'asc', 'daddy.name' => 'desc');
		$this->grid->multiSort = TRUE;
		$this->attachToPresenter($this->grid);
		$this->assertEquals($sort, $this->grid->sort);
	}



	public function testAttached_callsConfigure()
	{
		$qb = $this->getDoctrine()
			->getDao(__NAMESPACE__ . '\Fixtures\RootEntity')
			->createQueryBuilder('e');

		/** @var \PHPUnit_Framework_MockObject_MockObject|\Kdyby\Components\Grinder\Grid $grid */
		$grid = $this->getMock('Kdyby\Components\Grinder\Grid', array('configure', 'configureFilters'), array($this->getDoctrine(), $qb));
		$grid->expects($this->once())
			->method('configure')
			->with($this->isInstanceOf('Nette\Application\UI\Presenter'));
		$grid->expects($this->once())
			->method('configureFilters')
			->with($this->isInstanceOf('Nette\Application\UI\Presenter'));

		$this->attachToPresenter($grid);
	}



	public function testState_remembers()
	{
		$this->markTestIncomplete();
	}



	/**
	 * @expectedException Kdyby\InvalidStateException
	 * @expectedExceptionMessage Column with name 'name' already exists.
	 */
	public function testColumns_exceptionWhenAddingDuplicate()
	{
		$this->grid->addColumn('name');
		$this->grid->addColumn('name');
	}



	/**
	 * @expectedException Kdyby\InvalidStateException
	 * @expectedExceptionMessage Column name 'bullshit' is not valid.
	 */
	public function testColumns_exceptionWhenInvalidColumnName()
	{
		$this->grid->addColumn('bullshit');
	}



	/**
	 * @return array
	 */
	public function dataIsColumnNameValid()
	{
		return array(
			array('name'),
			array('daddy'),
			array('daddy.name'),
		);
	}



	/**
	 * @dataProvider dataIsColumnNameValid
	 *
	 * @param string $columnName
	 */
	public function testIsColumnNameValid($columnName)
	{
		$this->grid->addColumn($columnName);
	}



	public function testGetIterator_alwaysReturnsSameInstance()
	{
		$this->attachToPresenter($this->grid);
		$iterator = $this->grid->getIterator();
		$this->assertSame($iterator, $this->grid->getIterator());
	}

}
