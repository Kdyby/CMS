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
use Kdyby\Components\Grinder\Column;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class ColumnTest extends Kdyby\Tests\TestCase
{

	public function testGeneralGetters()
	{
		$column = new Column($grid = $this->mockGrid(), $name = 'my.name');
		$this->assertEquals($grid, $column->getGrid());
		$this->assertEquals($name, $column->getName());
	}



	public function testGetValue()
	{
		$value = 'Some value';
		$column = new Column($this->mockGrid($value), 'my.name');
		$this->assertEquals($value, $column->getValue());
	}



	public function testGetValue_filter()
	{
		$value = 10;
		$column = new Column($grid = $this->mockGrid($value), 'my.name');

		$filter = $this->getCallbackMock();
		$filter->expects($this->once())
			->method('__invoke')
			->with($this->equalTo($value), $this->equalTo($grid))
			->will($this->returnValue($result = 20));

		$column->addFilter($filter);
		$this->assertEquals($result, $column->getValue());
	}



	public function testGetValue_maxLength()
	{
		$value = "Some really long value";
		$column = new Column($this->mockGrid($value), 'my.name');
		$column->maxLength = 12;

		$this->assertEquals('Some really…', $column->getValue());
	}



	public function testGetValue_datetime()
	{
		$value = date_create();
		$column = new Column($this->mockGrid($value), 'my.name');
		$column->dateTimeFormat = $format = 'H:i:s d.m.Y';

		$this->assertEquals($value->format($format), $column->getValue());
	}



	public function testIsSorting()
	{
		$column = new Column($grid = $this->mockGrid(), 'my.name');
		$this->assertFalse($column->isSorting());

		$grid->sort[$column->name] = 'asc';
		$this->assertTrue($column->isSorting());
	}



	public function testIsSortable()
	{
		$column = new Column($this->mockGrid(NULL, TRUE), 'my.name');
		$this->assertTrue($column->isSortable());
	}



	public function testIsSortable_notWhenColumnSortingIsDisabled()
	{
		$column = new Column($this->mockGrid(/*NULL, TRUE not required */), 'my.name');
		$column->sortable = FALSE;
		$this->assertFalse($column->isSortable());
	}



	public function testIsSortable_notWhenGridSortingIsDisabled()
	{
		$column = new Column($grid = $this->mockGrid(/*NULL, TRUE not required */), 'my.name');
		$grid->disableSorting = TRUE;
		$this->assertFalse($column->isSortable());
	}



	public function testIsSortable_notWhenColumnNameIsNotValid()
	{
		$column = new Column($this->mockGrid(NULL, FALSE), 'my.name');
		$this->assertFalse($column->isSortable());
	}



	public function testGetSortType()
	{
		$column = new Column($grid = $this->mockGrid(NULL, TRUE), 'my.name');

		$this->assertEquals(NULL, $column->getSortType());

		$grid->sort[$column->name] = 'asc';
		$this->assertEquals('asc', $column->getSortType());

		$grid->sort[$column->name] = 'desc';
		$this->assertEquals('desc', $column->getSortType());

		$grid->sort[$column->name] = 'none';
		$this->assertEquals('none', $column->getSortType());
	}



	public function testGetCellControl()
	{
		$column = new Column($this->mockGrid(), 'my.name');

		$this->assertInstanceOf('Nette\Utils\Html', $cell = $column->getCellControl());
		$this->assertEquals(array(
			'class' => 'grinder-cell'
		), $cell->attrs);
	}



	public function testGetCellControl_editable()
	{
		$column = new Column($this->mockGrid(), 'my.name');
		$column->editable = TRUE;

		$this->assertInstanceOf('Nette\Utils\Html', $cell = $column->getCellControl());
		$this->assertEquals(array(
			'class' => 'grinder-cell',
			'data' => array(
				'grinder-cell' => '{"column":"my.name","item":null}'
			)
		), $cell->attrs);
	}



	public function testGetCellControl_tooLongValue()
	{
		$value = "Some really long value";
		$column = new Column($this->mockGrid($value), 'my.name');
		$column->maxLength = 5;

		$this->assertInstanceOf('Nette\Utils\Html', $cell = $column->getCellControl());
		$this->assertEquals(array(
			'title' => 'Some really long value',
			'class' => 'grinder-cell'
		), $cell->attrs);
	}



	public function testSortingControl_emptyWhenNotSortable()
	{
		$column = new Column($grid = $this->mockGrid(NULL, FALSE), 'my.name');
		$this->assertEquals(Nette\Utils\Html::el(), $column->getHeadControl());
	}



	public function testSortingControl_isSortingNone()
	{
		$column = new Column($grid = $this->mockGrid(NULL, TRUE), 'my.name');

		$grid->sort[$column->name] = 'none';
		$grid->expects($this->once())
			->method('lazyLink')
			->with($this->equalTo('sort!'), $this->equalTo(array('sort' => array('my.name' => 'asc'))))
			->will($this->returnValue('http://...'));

		$this->assertInstanceOf('Nette\Utils\Html', $sorting = $column->getHeadControl());
		$this->assertEquals(array(
			'href' => 'http://...',
			'class' => 'ajax sortable sort-none'
		), $sorting->attrs);
	}



	public function testSortingControl_isSortingAsc()
	{
		$column = new Column($grid = $this->mockGrid(NULL, TRUE), 'my.name');

		$grid->sort[$column->name] = 'asc';
		$grid->expects($this->once())
			->method('lazyLink')
			->with($this->equalTo('sort!'), $this->equalTo(array('sort' => array('my.name' => 'desc'))))
			->will($this->returnValue('http://...'));

		$this->assertInstanceOf('Nette\Utils\Html', $sorting = $column->getHeadControl());
		$this->assertEquals(array(
			'href' => 'http://...',
			'class' => 'ajax sortable sort-asc'
		), $sorting->attrs);
	}



	public function testSortingControl_isSortingDesc()
	{
		$column = new Column($grid = $this->mockGrid(NULL, TRUE), 'my.name');

		$grid->sort[$column->name] = 'desc';
		$grid->expects($this->once())
			->method('lazyLink')
			->with($this->equalTo('sort!'), $this->equalTo(array('sort' => array('my.name' => 'none'))))
			->will($this->returnValue('http://...'));

		$this->assertInstanceOf('Nette\Utils\Html', $sorting = $column->getHeadControl());
		$this->assertEquals(array(
			'href' => 'http://...',
			'class' => 'ajax sortable sort-desc'
		), $sorting->attrs);
	}



	/**
	 * @return array
	 */
	public function dataGetQueryExpr()
	{
		// FROM Entity e
		// JOIN e.author a
		// JOIN a.address addr
		$joins = array('e', 'e.author' => 'a', 'a.address' => 'addr');

		return array(
			array('e.content', 'e.content', $joins),
			array('a.name', 'e.author.name', $joins),
			array('addr.street', 'e.author.address.street', $joins),
		);
	}



	/**
	 * @dataProvider dataGetQueryExpr
	 *
	 * @param $expects
	 * @param $name
	 * @param array $aliases
	 */
	public function testGetQueryExpr($expects, $name, array $aliases)
	{
		$column = new Column($grid = $this->mockGrid(), $name);
		$qb = $this->mockQueryBuilder();
		$qb->expects($this->once())
			->method('getEntityAliases')
			->will($this->returnValue($aliases));

		$this->assertEquals($expects, $column->getQueryExpr($qb));
	}



	/**
	 * @param string $value
	 * @param bool $sortable
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|\Kdyby\Components\Grinder\Grid
	 */
	private function mockGrid($value = NULL, $sortable = NULL)
	{
		$doctrine = $this->getMockBuilder('Kdyby\Doctrine\Registry')
			->disableOriginalConstructor()
			->getMock();

		$qb = $this->mockQueryBuilder();
		$grid = $this->getMock('Kdyby\Components\Grinder\Grid', array(), array($qb, $doctrine));

		if ($value !== NULL) {
			$grid->expects($this->once())
				->method('getRecordProperty')
				->will($this->returnValue($value));
		}

		if ($sortable !== NULL && is_bool($sortable)) {
			$grid->expects($this->atLeastOnce())
				->method('isColumnNameValid')
				->will($this->returnValue($sortable));
		}

		return $grid;
	}



	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|\Kdyby\Doctrine\QueryBuilder
	 */
	private function mockQueryBuilder()
	{
		return $this->getMockBuilder('Kdyby\Doctrine\QueryBuilder')
			->disableOriginalConstructor()
			->getMock();
	}

}
