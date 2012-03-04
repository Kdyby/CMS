<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Components\Grinder\Columns;

use Kdyby;
use Kdyby\Components\Grinder;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class FormColumn extends Kdyby\Components\Grinder\Column
{

	/** @var \Nette\Forms\Controls\BaseControl */
	protected $control;



	/**
	 * @param \Kdyby\Components\Grinder\Grid $grid
	 * @param string $name
	 * @param \Nette\Forms\IControl $control
	 */
	public function __construct(Grinder\Grid $grid, $name, Nette\Forms\IControl $control)
	{
		parent::__construct($grid, $name);
		$this->control = $control;
		$this->buildControls();
	}



	/**
	 * @throws \Kdyby\InvalidStateException
	 */
	protected function buildControls()
	{
		if ($parent = $this->control->parent) {
			throw new Kdyby\InvalidStateException("Given form control is already attached to component.");
		}

		$form = $this->getGrid()->getForm();
		/** @var \Nette\Forms\Container $container */
		$container = $form->getColumnContainer($this->getName());
		foreach ($form->getRecordIds() as $index => $id) {
			$container->addComponent(clone $this->control, $index);
		}
	}



	/**
	 * @return \Nette\Forms\Controls\BaseControl
	 */
	protected function getIteratedControl()
	{
		$form = $this->getGrid()->getForm();
		$container = $form->getColumnContainer($this->getName());
		return $container[$this->getGrid()->getCurrentIndex()];
	}



	/**
	 * @param bool $need
	 *
	 * @return \Nette\Utils\Html
	 */
	public function getControl($need = FALSE)
	{
		$control = $this->getIteratedControl()->getControl();
		if (!$this->getGrid()->getForm()->isSubmitted()){
			$control->setValue(parent::getValue($need));
		}
		return $control;
	}



	/**
	 * @param bool $need
	 *
	 * @return \Nette\Utils\Html
	 */
	public function getValue($need = FALSE)
	{
		return $this->getControl($need);
	}



	/**
	 * @return bool
	 */
	public function isSortable()
	{
		return FALSE;
	}



	/**
	 * @return bool
	 */
	public function isEditable()
	{
		return FALSE;
	}

}
