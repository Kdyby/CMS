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
use Doctrine\Common\Collections\ArrayCollection;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 *
 * @method \Kdyby\Doctrine\Forms\Form getForm(bool $need = TRUE)
 */
class CollectionContainer extends Kdyby\Forms\Containers\Replicator implements Kdyby\Doctrine\Forms\IObjectContainer
{

	/** @var string */
	public $containerClass = 'Kdyby\Doctrine\Forms\EntityContainer';

	/** @var \Doctrine\Common\Collections\Collection */
	private $collection;

	/** @var \Kdyby\Components\Grinder\Grid */
	private $grid;



	/**
	 * @param \Kdyby\Components\Grinder\Grid $grid
	 */
	public function __construct(Grid $grid)
	{
		$this->grid = $grid;
		$this->collection = new ArrayCollection();
		parent::__construct(callback($grid, 'createColumnControls'));
		$this->monitor('Kdyby\Doctrine\Forms\Form');
		$this->addSubmit('save', 'Save');
	}



	/**
	 * @param bool $need
	 *
	 * @return \Nette\Application\UI\Presenter
	 */
	public function getPresenter($need = TRUE)
	{
		return $this->lookup('Nette\Application\UI\Presenter', $need);
	}



	/**
	 * @return \Kdyby\Doctrine\Forms\EntityMapper
	 */
	private function getMapper()
	{
		return $this->getForm()->getMapper();
	}



	/**
	 * @param \Nette\ComponentModel\Container $obj
	 */
	protected function attached($obj)
	{
		$this->initContainers();
		parent::attached($obj);
	}



	/**
	 * Initialize entity containers from given collection
	 */
	protected function initContainers()
	{
		if (!$this->getPresenter(FALSE)) {
			return; // only if attached to presenter
		}

		$this->getMapper()->assignCollection($this->collection, $this);
		if ($this->getForm()->isSubmitted()) {
			return; // only if not submitted
		}

		foreach ($this->collection as $index => $entity) {
			$this->createOne($index);
		}
	}



	/**
	 * @param integer $index
	 *
	 * @return \Kdyby\Doctrine\Forms\EntityContainer
	 */
	protected function createContainer($index)
	{
		$this->initItemsCollection();

		if (!$entity = $this->findEntity($index)) {
			return NULL;
		}
		return new Kdyby\Doctrine\Forms\EntityContainer($entity, $this->getMapper());
	}



	/**
	 * @param string $id
	 *
	 * @return object|NULL
	 */
	protected function findEntity($id)
	{
		foreach ($this->collection as $entity) {
			$ids = $this->getMapper()->getIdentifierValues($entity);
			if ($id == reset($ids)) {
				return $entity;
			}
		}

		return NULL;
	}



	/**
	 */
	protected function initItemsCollection()
	{
		if (!$this->collection->isEmpty()) {
			return;
		}

		foreach ($this->grid->getIterator()->getItems() as $item) {
			$this->collection[] = $item;
		}
	}

}
