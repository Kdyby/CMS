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
	}



	/**
	 * @internal
	 * @param \Kdyby\Components\Grinder\GridForm $form
	 * @throws \Kdyby\InvalidStateException
	 */
	public function buildControls(Grinder\GridForm $form)
	{
		if ($parent = $this->control->parent) {
			throw new Kdyby\InvalidStateException("Given form control is already attached to component.");
		}

		/** @var \Nette\Forms\Container $container */
		$container = $form->getColumnContainer($this->getName());
		foreach ($form->getRecordIds() as $index => $id) {
			$container->addComponent(clone $this->control, $index);
		}
	}



	/**
	 * @return \Kdyby\Components\Grinder\GridForm
	 */
	public function getForm()
	{
		return $this->getGrid()->getForm();
	}



	/**
	 * @return \Nette\Forms\Container
	 */
	protected function getFormContainer()
	{
		return $this->getForm()->getColumnContainer($this->getName());
	}



	/**
	 * @return \Nette\Forms\Controls\BaseControl
	 */
	protected function getIteratedControl()
	{
		return $this->getFormContainer()
			->getComponent($this->getGrid()->getCurrentIndex());
	}



	/**
	 * @param bool $need
	 *
	 * @return \Nette\Utils\Html
	 */
	public function getControl($need = FALSE)
	{
		$control = $this->getIteratedControl()->getControl();
		if (!$this->getForm()->isSubmitted()){
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
