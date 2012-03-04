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
use Nette\Forms\Controls\Checkbox;
use Nette\Utils\Html;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class CheckboxColumn extends FormColumn
{

	/**
	 * @param \Kdyby\Components\Grinder\Grid $grid
	 * @param $name
	 */
	public function __construct(Grinder\Grid $grid, $name)
	{
		parent::__construct($grid, $name, new Checkbox);
	}



	/**
	 * @param bool $need
	 *
	 * @return \Nette\Utils\Html
	 */
	public function getControl($need = FALSE)
	{
		$control = parent::getControl($need);
		$control->data('grinder-check-row', $this->getName());
		return $control;
	}



	/**
	 * @return \Nette\Utils\Html
	 */
	public function getHeadControl()
	{
		return Html::el('input', array(
			'type' => 'checkbox',
			'class' => 'select-all',
			'data-grinder-check-all' => $this->getName()
		));
	}

}
