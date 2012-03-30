<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Components\Grinder;

use Doctrine;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Kdyby;
use Kdyby\Doctrine\QueryBuilder;
use Nette;



/**
 * @author Filip Procházka
 */
class GridIterator extends \IteratorIterator
{
	/** @var \Kdyby\Components\Grinder\Grid */
	private $grid;

	/** @var \Kdyby\Doctrine\QueryBuilder */
	private $queryBuilder;

	/** @var object[] */
	private $items = array();



	/**
	 * @param \Kdyby\Components\Grinder\Grid $grid
	 * @param \Kdyby\Doctrine\QueryBuilder $queryBuilder
	 *
	 * @throws \Kdyby\Doctrine\QueryException
	 */
	public function __construct(Grid $grid, QueryBuilder $queryBuilder)
	{
		$this->grid = $grid;
		$this->queryBuilder = $queryBuilder;

		// filter
		$grid->getFilters()->apply($queryBuilder);

		// count pages
		$paginator = new Paginator($queryBuilder);
		try {
			$this->grid->getPaginator()->setItemCount($paginator->count());

		} catch (Doctrine\ORM\ORMException $e) {
			throw new Kdyby\Doctrine\QueryException($e, $paginator->getQuery(), $e->getMessage());
		}

		// set limit & offset
		$queryBuilder->setMaxResults($this->grid->getPaginator()->getLength());
		$queryBuilder->setFirstResult($this->grid->getPaginator()->getOffset());

		// sorting
		$grid->sortQuery($queryBuilder);

		// read items
		$query = $queryBuilder->getQuery();
		try {
			parent::__construct(new \ArrayIterator($this->items = $query->execute()));

		} catch (Doctrine\ORM\ORMException $e) {
			throw new Kdyby\Doctrine\QueryException($e, $query, $e->getMessage());
		}

		// items ids to form
		$form = $this->grid->getForm();
		$class = $this->getClass();
		$form->setRecordIds(array_map(function ($item) use ($class) {
			/** @var \Kdyby\Doctrine\Mapping\ClassMetadata $class */
			$id = $class->getIdentifierValues($item);
			return reset($id);
		}, $this->getItems()));

		// to allow checkAll functionality
		$form->setTotalResults($this->getTotalCount());
	}



	/**
	 * @return \Kdyby\Components\Grinder\Grid
	 */
	public function getGrid()
	{
		return $this->grid;
	}



	/**
	 * @return object[]
	 */
	public function getItems()
	{
		return $this->items;
	}



	/**
	 * @return int
	 */
	public function getTotalCount()
	{
		return $this->grid->getPaginator()->getItemCount();
	}



	/**
	 * Return the current element
	 *
	 * @return mixed Can return any type.
	 */
	public function current()
	{
		return $this->grid->bindRecord($this->key(), parent::current());
	}



	/**
	 * @return int|string
	 */
	public function getCurrentId()
	{
		$id = $this->getClass()->getIdentifierValues($this->getGrid()->getCurrent());
		return reset($id);
	}



	/**
	 * @return \Kdyby\Doctrine\Mapping\ClassMetadata
	 */
	private function getClass()
	{
		$class = $this->queryBuilder->getRootEntity();
		return $this->queryBuilder->getEntityManager()->getClassMetadata($class);
	}

}
