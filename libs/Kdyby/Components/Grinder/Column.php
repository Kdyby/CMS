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
use Kdyby\Components\Grinder;
use Kdyby\Doctrine\QueryBuilder;
use Nette;
use Nette\Utils\Html;
use Nette\Utils\Strings;



/**
 * Grid column
 *
 * @author Filip Procházka
 *
 * @property-read string $name
 * @property-read \Kdyby\Components\Grinder\Grid $grid
 * @property-read mixed $value
 */
class Column extends Nette\Object
{

	/** @var string */
	public $dateTimeFormat = "j.n.Y G:i";

	/** @var int */
	public $maxLength = 0;

	/** @var bool */
	public $sortable = TRUE;

	/** @var bool */
	public $editable = FALSE;

	/** @var string|\Nette\Utils\Html */
	public $caption;

	/** @var \Kdyby\Components\Grinder\Grid */
	private $grid;

	/** @var string */
	private $name;

	/** @var mixed */
	private $value;

	/** @var \Nette\Callback[] */
	private $filters = array();



	/**
	 * @param \Kdyby\Components\Grinder\Grid $grid
	 * @param string $name
	 */
	public function __construct(Grinder\Grid $grid, $name)
	{
		$this->grid = $grid;
		$this->name = $name;
	}



	/**
	 * @return \Kdyby\Components\Grinder\Grid
	 */
	public function getGrid()
	{
		return $this->grid;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * @return mixed
	 */
	final public function getValue()
	{
		if ($this->value) {
			return $this->value;
		}

		$value = $this->grid->getRecordProperty($this->name);
		foreach ($this->filters as $filter) {
			$value = $filter($value, $this->grid);
		}

		if (is_string($value) && $this->maxLength !== 0) {
			$value = Strings::truncate($value, $this->maxLength);
		}

		if ($value instanceof \DateTime) {
			/** @var \DateTime $value */
			$value = $value->format($this->dateTimeFormat);
		}

		return $value;
	}



	/**
	 * @param callback $filter
	 *
	 * @return \Kdyby\Components\Grinder\Column
	 */
	public function addFilter($filter)
	{
		$this->filters[] = callback($filter);
		return $this;
	}



	/**
	 * @return bool
	 */
	public function isSorting()
	{
		return isset($this->grid->sort[$this->name]);
	}



	/**
	 * @internal
	 * @return string
	 */
	public function getSortType()
	{
		if (!$this->isSorting()) {
			return NULL;
		}

		return $this->grid->sort[$this->name];
	}



	/**
	 * @internal
	 * @return \Nette\Utils\Html
	 */
	public function getSortingControl()
	{
		if (!$this->sortable || $this->grid->disableOrder) {
			return Html::el();
		}

		return Html::el('a', array(
			'href' => $this->grid->lazyLink('sort!', array('sort' => $this->getSorting())),
			'class' => 'ajax'
		));
	}



	/**
	 * @return array
	 */
	protected function getSorting()
	{
		$type = $this->getNextSortingType();

		$sort = $this->grid->sort;
		if ($this->grid->multiOrder) {
			$sort[$this->name] = $type;

		} else {
			$sort = array($this->name => $type);
		}

		return (array)$sort;
	}



	/**
	 * @return string|NULL
	 */
	private function getNextSortingType()
	{
		$types = array('asc', 'desc', 'none', 'asc');
		$i = array_search($this->getSortType(), $types);
		return isset($types[$i]) ? $types[$i+1] : reset($types);
	}



	/**
	 * @internal
	 * @param \Kdyby\Doctrine\QueryBuilder $query
	 *
	 * @return string|NULL
	 */
	public function getQueryExpr(QueryBuilder $query)
	{
		// find alias pairs
		$aliases = $query->getEntityAliases();
		$alias = reset($aliases);

		// list fields
		$fields = explode('.', $this->name);
		while ($field = array_shift($fields)) {
			if (isset($aliases[$expr = $alias . '.' . $field])) {
				$alias = $aliases[$expr];

			} elseif (!$fields) {
				return $expr;
			}
		}

		return NULL;
	}

}
