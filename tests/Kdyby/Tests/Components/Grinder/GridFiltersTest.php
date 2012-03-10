<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Tests\Components\Grinder;

use Doctrine;
use Kdyby;
use Kdyby\Components\Grinder\GridFilters;
use Kdyby\Components\Grinder\Filters;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class GridFiltersTest extends Kdyby\Tests\TestCase
{

	/**
	 * @return array
	 */
	public function dataAdd_creating()
	{
		return array(
			array('my.name', $control = new Nette\Forms\Controls\TextInput, '=', new Filters\FormInputFilter($control)),
			array('my.name', $callback = function () { }, '=', new Filters\CallbackFilter($callback)),
			array('my.name', $callback = callback('trim'), '=', new Filters\CallbackFilter($callback)),
			array('my.name', $param = array(new ControlMock, 'param'), '=', new Filters\ParameterFilter($param)),
			array('my.name', $value = 'string', '=', new Filters\ValueFilter($value)),
			array('my.name', $value = 10, '=', new Filters\ValueFilter($value)),
			array('my.name', $value = TRUE, '=', new Filters\ValueFilter($value)),
			array('my.name', $value = NULL, '=', new Filters\ValueFilter($value)),
		);
	}



	/**
	 * @dataProvider dataAdd_creating
	 *
	 * @param string $column
	 * @param mixed $value
	 * @param string $operation
	 * @param \Kdyby\Components\Grinder\QueryFilter $expected
	 */
	public function testAdd_creating($column, $value, $operation, $expected)
	{
		$filters = new GridFilters($this->mockGrid());

		$filter = $filters->add($column, $value, $operation);
		$this->assertInstanceOf('Kdyby\Components\Grinder\QueryFilter', $filter);

		$expected->column = $column;
		$this->assertEquals($expected, $filter);
	}



	/**
	 */
	public function testAdd_createdQueryFilter()
	{
		$filters = new GridFilters($this->mockGrid());

		$value = new Kdyby\Components\Grinder\Filters\ValueFilter("string value");
		$filter = $filters->add('my.name', $value, '=');

		$this->assertSame($value, $filter);
		$this->assertEquals('my.name', $filter->column);
		$this->assertEquals('=', $filter->operator);
		$this->assertSame(array($value), $filters->getFilters('my.name'));
	}



	/**
	 * @expectedException Kdyby\InvalidStateException
	 * @expectedExceptionMessage There is no registered filter class, that would be able to process string.
	 */
	public function testAdd_valueCannotBeHandledException()
	{
		$filters = new GridFilters($this->mockGrid());
		$filters->handlers = array();
		$filters->add('my.name', "value");
	}



	public function testApply_filtersOnQueryBuilder()
	{
		$filters = new GridFilters($this->mockGrid());
		$qb = $this->mockQueryBuilder();

		$filters->add('my.one', $one = $this->mockQueryFilter());
		$one->expects($this->once())
			->method('apply')
			->with($this->equalTo($filters), $this->equalTo($qb));

		$filters->add('my.two', $two = $this->mockQueryFilter());
		$two->expects($this->once())
			->method('apply')
			->with($this->equalTo($filters), $this->equalTo($qb));

		$filters->apply($qb);
	}



	/**
	 * @return array
	 */
	public function dataFilterQuery()
	{
		return array(
			array('column', '=', 10, 'e.column', "e.column = :column_%s"),
			array('column', '=', "string", 'e.column', "e.column = :column_%s"),
			array('column', '=', FALSE, 'e.column', "e.column = :column_%s"),
			array('column', '=', TRUE, 'e.column', "e.column = :column_%s"),
			array('column', '=', NULL, 'e.column', "e.column IS NULL"),
			array('column', '!=', NULL, 'e.column', "e.column IS NOT NULL"),
		);
	}



	/**
	 * @dataProvider dataFilterQuery
	 *
	 * @param string $columnName
	 * @param string $operator
	 * @param mixed $value
	 * @param string $queryExpr
	 * @param string $whereRule
	 */
	public function testFilterQuery($columnName, $operator, $value, $queryExpr, $whereRule)
	{
		$filters = new GridFilters($grid = $this->mockGrid());

		if ($value === NULL) { // will call ->andWhere() & use $qb->expr()
			$qb = $this->mockQueryBuilder($whereRule, NULL, TRUE);

		} else { // will call ->andWhere() & ->setParameter()
			$qb = $this->mockQueryBuilder($whereRule, array(
				'column' => $columnName,
				'value' => $value
			));
		}

		// $grid->getColumn()->getQueryExpr()
		$column = $this->mockGridColumn($grid, $columnName);
		$column->expects($this->once())
			->method('getQueryExpr')
			->with($this->equalTo($qb))
			->will($this->returnValue($queryExpr));

		$filters->filterQuery($qb, $columnName, $operator, $value);
	}



	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|\Kdyby\Components\Grinder\Grid
	 */
	private function mockGrid()
	{
		$doctrine = $this->getMockBuilder('Kdyby\Doctrine\Registry')
			->disableOriginalConstructor()
			->getMock();

		$qb = $this->mockQueryBuilder();
		$grid = $this->getMock('Kdyby\Components\Grinder\Grid', array(), array($doctrine, $qb));

		$grid->expects($this->any())
			->method('isColumnNameValid')
			->will($this->returnValue(TRUE));

		return $grid;
	}



	/**
	 * @param string $whereRule
	 * @param array|NULL $parameter
	 * @param bool $expr
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|\Kdyby\Doctrine\QueryBuilder
	 */
	private function mockQueryBuilder($whereRule = NULL, array $parameter = NULL, $expr = FALSE)
	{
		$qb = $this->getMockBuilder('Kdyby\Doctrine\QueryBuilder')
			->disableOriginalConstructor()
			->getMock();

		if ($expr !== FALSE) {
			$qb->expects($this->once())
				->method('expr')
				->will($this->returnValue(new Doctrine\ORM\Query\Expr));
		}

		if ($whereRule !== NULL) {
			$qb->expects($this->once())
				->method('andWhere')
				->with($this->matches($whereRule));

			if ($parameter !== NULL) {
				$qb->expects($this->once())
					->method('setParameter')
					->with($this->matches($parameter['column'] . '_%s'), $this->equalTo($parameter['value']));
			}
		}

		return $qb;
	}



	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|\Kdyby\Components\Grinder\Filters\ValueFilter
	 */
	private function mockQueryFilter()
	{
		return $this->getMockBuilder('Kdyby\Components\Grinder\Filters\ValueFilter')
			->disableOriginalConstructor()
			->getMock();
	}



	/**
	 * @param \PHPUnit_Framework_MockObject_MockObject $grid
	 * @param string $columnName
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|\Kdyby\Components\Grinder\Column
	 */
	private function mockGridColumn(\PHPUnit_Framework_MockObject_MockObject $grid, $columnName)
	{
		$column = $this->getMockBuilder('Kdyby\Components\Grinder\Column')
			->disableOriginalConstructor()
			->getMock();

		$grid->expects($this->once())
			->method('getColumn')
			->with($this->equalTo($columnName))
			->will($this->returnValue($column));

		return $column;
	}

}



/**
 */
class ControlMock extends Nette\Application\UI\Control
{

	/** @persistent */
	public $param;

}
