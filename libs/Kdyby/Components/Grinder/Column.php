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
use Kdyby\Doctrine\Forms\EntityContainer;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\Mapping\ClassMetadata;
use Nette;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Nette\Utils\Json;



/**
 * Grid column
 *
 * @author Filip Procházka
 *
 * @property-read string $name
 * @property-read \Kdyby\Components\Grinder\Grid $grid
 * @property-read mixed $value
 * @method \Kdyby\Components\Grinder\Column setDateTimeFormat(string $format)
 * @method \Kdyby\Components\Grinder\Column setMaxLength(int $length)
 * @method \Kdyby\Components\Grinder\Column setMaxTitleLength(int $length)
 * @method \Kdyby\Components\Grinder\Column setSortable(bool $sortable)
 * @method \Kdyby\Components\Grinder\Column setEditable(bool $editable)
 * @method \Kdyby\Components\Grinder\Column setCaption(bool $caption)
 */
class Column extends Nette\Object
{

	/** @var string */
	public $dateTimeFormat = "j.n.Y G:i";

	/** @var int */
	public $maxLength = 0;

	/** @var int */
	public $maxTitleLength = 1000;

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

	/** @var mixed */
	protected $fullLengthValue;

	/** @var \Nette\Callback[] */
	private $filters = array();



	/**
	 * @param \Kdyby\Components\Grinder\Grid $grid
	 * @param string $name
	 */
	public function __construct(Grid $grid, $name)
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
	 * @param bool $need
	 *
	 * @return mixed
	 */
	public function getValue($need = TRUE)
	{
		if ($this->value) {
			return $this->value;
		}

		$value = $this->grid->getRecordProperty($this->name, $need);
		foreach ($this->filters as $filter) {
			$value = $filter($value, $this->grid);
		}

		if (is_string($value) && $this->maxLength !== 0) {
			$this->fullLengthValue = $value;
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
	 * @param \Kdyby\Doctrine\Forms\EntityContainer|\Kdyby\Application\UI\Form $container
	 * @param \Kdyby\Doctrine\Mapping\ClassMetadata $class
	 *
	 * @return
	 */
	public function createFormControl(EntityContainer $container, ClassMetadata $class)
	{
		if (!$this->isEditable()) {
			return;
		}

		$path = explode('.', $this->name);
		if ($class->hasField($field = end($path))) { // $class is parent
			switch ($class->getTypeOfField($field)) {
				case 'datetime':
				case 'datetimez':
					$container->addDatetime($field);
					break;

				case 'date':
					$container->addDate($field);
					break;

				case 'time':
					$container->addTime($field);
					break;

				default:
					$container->addText($field);
			}

		} else { // $class is current

		}
	}



	/**
	 * @return bool
	 */
	public function isEditable()
	{
		return $this->editable;
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
		if (!$this->isSorting() || !$this->isSortable()) {
			return NULL;
		}

		return $this->grid->sort[$this->name];
	}



	/**
	 * @return bool
	 */
	public function isSortable()
	{
		return $this->sortable
			&& !$this->grid->disableSorting
			&& $this->grid->isColumnNameValid($this->name, TRUE);
	}



	/**
	 * @internal
	 * @return \Nette\Utils\Html
	 */
	public function getCellControl()
	{
		$el = Html::el('td')->class('grinder-cell');

		if ($this->isEditable()){
			$el->data('grinder-cell', Json::encode(array(
				'column' => $this->name,
				'item' => $this->getGrid()->getCurrentRecordId()
			)));
		}

		$value = $this->getValue();
		if ($this->fullLengthValue) {
			$el->title = Strings::truncate($this->fullLengthValue, $this->maxTitleLength);
		}
		if (is_scalar($value)) {
			$el->setText($value);
		}

		return $el;
	}



	/**
	 * @internal
	 * @return \Nette\Utils\Html
	 */
	public function getHeadControl()
	{
		if (!$this->isSortable()) {
			return Html::el();
		}

		$class = array('ajax', 'sortable');
		if ($type = $this->getSortType()) {
			$class[] = 'sort-' . $type;
		}

		return Html::el('a', array(
			'href' => $this->grid->lazyLink('sort!', array('sort' => $this->getSorting())),
			'class' => implode(' ', $class)
		))->setText($this->caption);
	}



	/**
	 * @return array
	 */
	protected function getSorting()
	{
		$type = $this->getNextSortingType();

		$sort = $this->grid->sort;
		if ($this->grid->multiSort) {
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
	 * Translates column name "e.author.address.street" to dql-understandable format "addr.street"
	 *
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

		return $alias;
	}



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

}
