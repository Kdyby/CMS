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
use Nette;
use Nette\Forms\Controls\SubmitButton;



/**
 * @author Filip Procházka
 */
class GridForm extends Kdyby\Doctrine\Forms\Form
{
	/** @var \Kdyby\Components\Grinder\CollectionContainer */
	private $rows;



	/**
	 * @param \Kdyby\Doctrine\Registry $doctrine
	 * @param \Kdyby\Components\Grinder\Grid $grid
	 */
	public function __construct(Kdyby\Doctrine\Registry $doctrine, Grid $grid)
	{
		parent::__construct($doctrine);
		$this->monitor('Kdyby\Components\Grinder\Grid');

		// editable rows
		$this->rows = new CollectionContainer($grid);
		$this->addComponent($this->rows, 'entity');
	}



	/**
	 */
	protected function configure()
	{
		$this->addContainer('columns');
		$this->addContainer('toolbar');

		// for javascript to select all rows
		$this->addCheckbox('checkAll', 'Select all %d results')
			->setDefaultValue(FALSE);
	}



	/**
	 * @param int $count
	 */
	public function setTotalResults($count)
	{
		$this['checkAll']->setAttribute('data-grinder-checkAll', $count);
		$this['checkAll']->caption = str_replace('%d', $count, $this['checkAll']->caption);
	}



	/**
	 * @param \Nette\ComponentModel\IComponent $obj
	 */
	protected function attached($obj)
	{
		if ($obj instanceof Nette\Application\UI\Presenter) {
			// build all form column controls
			foreach ($this->getFormColumns() as $column) {
				$column->buildControls($this);
			}

			// force create before signal!
			$this->getIdsContainer();

			// wrap all click calls
			$this->wrapToolbarCallbacks();
		}

		parent::attached($obj);
	}



	/**
	 * Wrap all click calls
	 */
	protected function wrapToolbarCallbacks()
	{
		$selectColumn = $this->getSelectColumn();
		foreach ($this->getToolbar()->getControls() as $control) {
			if (!$control instanceof SubmitButton) {
				continue;
			}

			$onClick = array();
			/** @var Nette\Forms\Controls\SubmitButton $control */
			foreach ($control->onClick as $callback) {
				$onClick[] = function (SubmitButton $button) use ($callback, $selectColumn) {
					/** @var \Kdyby\Components\Grinder\Columns\CheckboxColumn $selectColumn */
					callback($callback)->invoke($selectColumn->createResult(), $button);
				};
			}
			$control->onClick = $onClick;
		}
	}



	/**
	 * @return \Kdyby\Components\Grinder\Columns\FormColumn[]
	 */
	protected function getFormColumns()
	{
		return $this->getGrid()->getColumns('Kdyby\Components\Grinder\Columns\FormColumn');
	}



	/**
	 * @return \Nette\Forms\Container
	 */
	public function getToolbar()
	{
		return $this->getComponent('toolbar');
	}



	/**
	 * @return \Kdyby\Components\Grinder\Columns\CheckboxColumn
	 */
	public function getSelectColumn()
	{
		$checks = iterator_to_array($this->getGrid()->getColumns('Kdyby\Components\Grinder\Columns\CheckboxColumn'));
		return reset($checks);
	}



	/**
	 * @param string $column
	 *
	 * @return \Nette\Forms\Container
	 */
	public function getColumnContainer($column)
	{
		$column = str_replace('.', '__', $column);
		if (!$container = $this['columns']->getComponent($column, FALSE)) {
			$container = $this['columns']->addContainer($column);
		}
		return $container;
	}



	/**
	 * @return \Kdyby\Components\Grinder\CollectionContainer
	 */
	public function getRows()
	{
		return $this->rows;
	}



	/**
	 * @return \Nette\Forms\Container
	 */
	protected function getIdsContainer()
	{
		if ($container = $this->getComponent('ids', FALSE)) {
			return $container;
		}

		$container = $this->addContainer('ids');
		$perPage = $this->getGrid()->getItemsPerPage();
		for ($index = 0; $index < $perPage; $index++) {
			$container->addHidden($index);
		}

		return $container;
	}



	/**
	 * @param boolean $need
	 *
	 * @return \Kdyby\Components\Grinder\Grid
	 */
	public function getGrid($need = TRUE)
	{
		return $this->lookup('Kdyby\Components\Grinder\Grid', $need);
	}



	/**
	 * @param array $ids
	 */
	public function setRecordIds(array $ids)
	{
		$this->getIdsContainer()->setDefaults(array_values($ids));
	}



	/**
	 * @return array
	 */
	public function getRecordIds()
	{
		return $this->getIdsContainer()->getValues(TRUE);
	}

}
