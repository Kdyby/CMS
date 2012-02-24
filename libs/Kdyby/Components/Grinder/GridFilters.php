<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Components\Grinder;

use Kdyby;
use Kdyby\Doctrine\QueryBuilder;
use Nette;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class GridFilters extends Nette\Object implements \ArrayAccess
{
	/** @var array */
	public static $handlers = array(
		'Kdyby\Components\Grinder\Filters\FormInputFilter',
		'Kdyby\Components\Grinder\Filters\ParameterFilter',
		'Kdyby\Components\Grinder\Filters\CallbackFilter',
		'Kdyby\Components\Grinder\Filters\ValueFilter',
	);

	/** @var \Kdyby\Components\Grinder\Grid */
	private $grid;

	/** @var \Kdyby\Components\Grinder\QueryFilter[] */
	private $filters = array();



	/**
	 * @param \Kdyby\Components\Grinder\Grid $grid
	 */
	public function __construct(Grid $grid)
	{
		$this->grid = $grid;
	}



	/**
	 * @return \Kdyby\Components\Grinder\Grid
	 */
	public function getGrid()
	{
		return $this->grid;
	}



	/**
	 * @param string $name
	 * @param mixed $value
	 * @param string $operation
	 *
	 * @return \Kdyby\Components\Grinder\QueryFilter
	 */
	public function add($name, $value, $operation = '=')
	{
		if (!$this->offsetExists($name)) {
			throw new Kdyby\InvalidStateException("Column name '$name' is not valid.");
		}

		if ($value instanceof QueryFilter) {
			/** @var \Kdyby\Components\Grinder\QueryFilter $value */
			$value->column = $name;
			$value->operator = $operation;
			return $this->filters[$name][] = $value;
		}

		// find appropriate handler
		foreach (static::$handlers as $handler) {
			/** @var \Kdyby\Components\Grinder\QueryFilter $handler */
			if ($handler::canHandle($value)) {
				$handler = new $handler($value);
				break;
			}
		}

		if (!isset($handler) || !$handler instanceof QueryFilter) {
			throw new Kdyby\InvalidStateException("There is no registered filter class, that would be able to process " . Kdyby\Tools\Mixed::getType($value) . ".");
		}

		$handler->column = $name;
		$handler->operator = $operation;
		return $this->filters[$name][] = $handler;
	}



	/**
	 * @param string $column
	 * @return \Kdyby\Components\Grinder\QueryFilter[]|NULL
	 */
	public function getFilters($column)
	{
		return isset($this->filters[$column]) ? $this->filters[$column] : NULL;
	}



	/**
	 * @param \Kdyby\Doctrine\QueryBuilder $queryBuilder
	 */
	public function apply(QueryBuilder $queryBuilder)
	{
		foreach (Nette\Utils\Arrays::flatten($this->filters) as $filter) {
			/** @var \Kdyby\Components\Grinder\QueryFilter $filter*/
			$filter->apply($this, $queryBuilder);
		}
	}



	/**
	 * @param \Kdyby\Doctrine\QueryBuilder $qb
	 * @param string $column
	 * @param string $operator
	 * @param mixed $value
	 */
	public function filterQuery(QueryBuilder $qb, $column, $operator, $value)
	{
		$expr = $qb->expr();
		$queryColumn = $this->grid->getColumn($column)->getQueryExpr($qb);

		if ($value === NULL) {
			if ($operator === '=') {
				$qb->andWhere($expr->isNull($queryColumn));

			} elseif ($operator === '!=') {
				$qb->andWhere($expr->isNotNull($queryColumn));

			} else {
				throw new Kdyby\InvalidStateException('Invalid combination of value NULL and operator "' . $operator . '". ');
			}

		} else {
			$uniqParam = Strings::replace($column, array('~[^a-z0-9]+~' => NULL)). '_' . Strings::random(3);

			if (is_array($value)) {
				$operator = strtr($operator, array(
					'!=' => 'IS NOT IN',
					'=' => 'IS IN',
				));
			}

			$qb->andWhere($queryColumn . ' ' . $operator . ' :' . $uniqParam);
			$qb->setParameter($uniqParam, $value);
		}
	}



	/**
	 * @param string $offset
	 *
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->filters[$offset]) || $this->grid->isColumnNameValid($offset);
	}



	/**
	 * @param string $offset
	 * @return \Kdyby\Components\Grinder\GridFiltersFluent
	 */
	public function offsetGet($offset)
	{
		if (!$this->offsetExists($offset)) {
			throw new Kdyby\InvalidStateException("Column name '$offset' is not valid.");
		}

		return new GridFiltersFluent($this, $offset);
	}



	/**
	 * @param string $offset
	 * @param \Kdyby\Components\Grinder\QueryFilter $value
	 *
	 * @return \Kdyby\Components\Grinder\QueryFilter
	 */
	public function offsetSet($offset, $value)
	{
		return $this->add($offset, $value);
	}



	/**
	 * @param string $offset
	 */
	public function offsetUnset($offset)
	{
		unset($this->filters[$offset]);
	}

}



/**
 * @internal
 * @method \Kdyby\Components\Grinder\QueryFilter equals($value)
 * @method \Kdyby\Components\Grinder\QueryFilter notEquals($value)
 * @method \Kdyby\Components\Grinder\QueryFilter greaterThan($value)
 * @method \Kdyby\Components\Grinder\QueryFilter greaterOrEquals($value)
 * @method \Kdyby\Components\Grinder\QueryFilter lesserThan($value)
 * @method \Kdyby\Components\Grinder\QueryFilter lesserOrEquals($value)
 *
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class GridFiltersFluent extends Nette\Object
{
	/** @var array */
	public static $methods = array(
		'equals' => '=',
		'notequals' => '!=',
		'greaterthan' => '>',
		'greaterorequals' => '>=',
		'lesserthan' => '<',
		'lesserorequals' => '<=',
	);

	/** @var \Kdyby\Components\Grinder\GridFilters */
	private $filters;

	/** @var string */
	private $column;



	/**
	 * @param \Kdyby\Components\Grinder\GridFilters $filters
	 * @param string $column
	 */
	public function __construct(GridFilters $filters, $column)
	{
		$this->filters = $filters;
		$this->column = $column;
	}



	/**
	 * @param string $operation
	 * @param array $args
	 *
	 * @return \Kdyby\Components\Grinder\QueryFilter
	 */
	public function __call($operation, $args)
	{
		if (!$args) {
			throw new Kdyby\InvalidArgumentException("No value was given to filters['{$this->column}']->$operation().");
		}

		$value = reset($args);
		if (isset(static::$methods[$method = strtolower($operation)])) {
			$operation = static::$methods[$method];
		}
		$operation = Strings::replace($operation, array('~[a-z](?=[A-Z])~' => '$0 '));
		return $this->filters->add($this->column, $value, strtoupper($operation));
	}

}
