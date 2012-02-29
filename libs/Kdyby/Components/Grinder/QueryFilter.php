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



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 *
 * @method \Kdyby\Components\Grinder\QueryFilter setOperator(string $operator)
 * @method \Kdyby\Components\Grinder\QueryFilter setIgnoreNull(boolean $ignore)
 * @method \Kdyby\Components\Grinder\QueryFilter setColumn(string $column)
 */
abstract class QueryFilter extends Nette\Object
{

	/** @var string */
	public $operator = '=';

	/** @var boolean */
	public $ignoreNull = TRUE;

	/** @var string */
	public $column;

	/** @var \Kdyby\Components\Grinder\GridFilters */
	protected $filters;



	/**
	 * @param \Kdyby\Components\Grinder\GridFilters $filters
	 * @param \Kdyby\Doctrine\QueryBuilder $qb
	 */
	public function apply(GridFilters $filters, QueryBuilder $qb)
	{
		$this->filters = $filters;
		if (($value = $this->getValue()) !== NULL || $this->ignoreNull === FALSE) {
			$filters->filterQuery($qb, $this->column, $this->operator, $value);
		}
	}



	/**
	 * @return string|array
	 */
	abstract protected function getValue();



	/**
	 * @param string $name
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		return Nette\ObjectMixin::callProperty($this, $name, $args);
	}



	/**
	 * @param mixed $value
	 * @return bool
	 */
	public static function canHandle($value)
	{
		return FALSE;
	}

}
