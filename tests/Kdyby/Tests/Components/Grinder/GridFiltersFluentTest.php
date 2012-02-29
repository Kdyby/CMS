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
use Kdyby\Components\Grinder\GridFiltersFluent;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class GridFiltersFluentTest extends Kdyby\Tests\TestCase
{

	/**
	 * @return array
	 */
	public function dataCallFilter()
	{
		return array(
			array('equals', '='),
			array('notEquals', '!='),
			array('greaterThan', '>'),
			array('greaterOrEquals', '>='),
			array('lesserThan', '<'),
			array('lesserOrEquals', '<='),
		);
	}



	/**
	 * @dataProvider dataCallFilter
	 *
	 * @param string $method
	 * @param string $operation
	 */
	public function testCallFilter($method, $operation)
	{
		$value = "my value";
		$fluentFilter = new GridFiltersFluent($filters = $this->mockFilters(), 'my.name');

		$filters->expects($this->once())
			->method('add')
			->with($this->equalTo('my.name'), $this->equalTo($value), $this->equalTo($operation))
			->will($this->returnValue('createdFilter'));

		$this->assertEquals('createdFilter', $fluentFilter->$method($value));
	}



	/**
	 * @expectedException Kdyby\InvalidArgumentException
	 * @expectedExceptionMessage No value was given to filters['my.name']->equals().
	 */
	public function testCallFilter_exceptionWithoutArgument()
	{
		$fluentFilter = new GridFiltersFluent($this->mockFilters(), 'my.name');

		/** @noinspection PhpParamsInspection */
		$fluentFilter->equals();
	}



	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|\Kdyby\Components\Grinder\GridFilters
	 */
	private function mockFilters()
	{
		return $this->getMockBuilder('Kdyby\Components\Grinder\GridFilters')
			->disableOriginalConstructor()
			->getMock();
	}

}
