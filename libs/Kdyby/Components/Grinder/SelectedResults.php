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
use Kdyby;
use Kdyby\Doctrine\Dao;
use Kdyby\Doctrine\QueryBuilder;
use Nette;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class SelectedResults extends Nette\Object
{

	/** @var bool */
	private $selectedAll;

	/** @var \Kdyby\Components\Grinder\Grid */
	private $grid;

	/** @var \Kdyby\Doctrine\QueryBuilder */
	private $queryBuilder;

	/** @var array */
	private $ids;

	/** @var object[] */
	private $entities;



	/**
	 * @param \Kdyby\Components\Grinder\Grid $grid
	 * @param bool $all
	 * @param array $ids
	 */
	public function __construct(Grid $grid, $all = FALSE, array $ids = NULL)
	{
		$this->grid = $grid;
		$this->selectedAll = $all;
		$this->ids = $ids;

		// ask grid for query builder
		$grid->bindFilteredQuery($this);
	}



	/**
	 * @internal
	 * @param \Kdyby\Doctrine\QueryBuilder $queryBuilder
	 *
	 * @throws \Kdyby\InvalidStateException
	 */
	public function bindItemsQuery(QueryBuilder $queryBuilder)
	{
		if ($this->queryBuilder !== NULL) {
			throw new Kdyby\InvalidStateException("QueryBuilder cannot be bind repeatedly.");
		}

		$this->queryBuilder = $queryBuilder;
	}



	/**
	 * @return \Kdyby\Doctrine\Mapping\ClassMetadata
	 */
	private function getClass()
	{
		$class = $this->queryBuilder->getRootEntity();
		return $this->queryBuilder->getEntityManager()->getClassMetadata($class);
	}



	/**
	 * @return string
	 */
	private function getPrimary()
	{
		$ids = $this->getClass()->getIdentifierFieldNames();
		return reset($ids);
	}



	/**
	 * @return array|int[]
	 */
	private function fetchIds()
	{
		$qb = clone $this->queryBuilder;
		$qb->setParameters($this->queryBuilder->getParameters());
		$qb->resetDQLPart('select');
		$qb->addSelect($qb->getRootAlias() . '.' . $this->getPrimary());
		return $qb->getQuery()->getArrayResult();
	}



	/**
	 * @return array
	 */
	public function getIds()
	{
		if ($this->selectedAll && !$this->ids) {
			$this->ids = $this->fetchIds();
		}

		return $this->ids;
	}



	/**
	 * @return array|\object[]
	 */
	public function getEntities()
	{
		if ($this->entities === NULL) {
			if ($this->selectedAll) {
				$this->entities = $this->queryBuilder
					->getQuery()
					->getResult();

			} elseif ($this->ids) {
				$qb = $this->queryBuilder;
				$alias = $qb->getRootAlias();
				$qb->andWhere($alias . ' IN (:spec_ids)')
					->setParameter('spec_ids', $this->ids);

				// todo: maybe use repository?
				$this->entities = $qb->getQuery()->getResult();

			} else {
				$this->entities = array();
			}
		}

		return $this->entities;
	}



	/**
	 * @throws \Kdyby\Doctrine\QueryException
	 * @return \Kdyby\Doctrine\QueryBuilder
	 */
	public function delete()
	{
		$qb = $this->getModifyQuery();
		$qb->delete($qb->getRootEntity(), 'e');

		try {
			return $qb->getQuery()->execute();

		} catch (Doctrine\ORM\Query\QueryException $e) {
			throw new Kdyby\Doctrine\QueryException($e, $qb->getQuery(), $e->getMessage());
		}
	}



	/**
	 * @param array $values
	 *
	 * @throws \Kdyby\Doctrine\QueryException
	 * @return mixed
	 */
	public function update(array $values = array())
	{
		$qb = $this->getModifyQuery();
		$qb->update($qb->getRootEntity(), 'e');

		foreach ($values as $key => $value) {
			$param = $key . '_' . Strings::random(3);
			$qb->set('e.' . $key, ':' . $param);
			$qb->setParameter($param, $value);
		}

		try {
			return $qb->getQuery()->execute();

		} catch (Doctrine\ORM\Query\QueryException $e) {
			throw new Kdyby\Doctrine\QueryException($e, $qb->getQuery(), $e->getMessage());
		}
	}



	/**
	 * @return \Kdyby\Doctrine\QueryBuilder
	 */
	private function getModifyQuery()
	{
		$qb = $this->grid->getRepository()->createQueryBuilder('e');
		$qb->where('e IN (:ids)');
		$qb->setParameter('ids', $this->getIds());
		return $qb;
	}

}
