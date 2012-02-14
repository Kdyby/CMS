<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Components\Grinder\Filters;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class FormInputFilter extends Kdyby\Components\Grinder\QueryFilter
{

	/** @var \Nette\Forms\IControl */
	public $control;



	/**
	 * @param \Nette\Forms\IControl $control
	 */
	public function __construct(Nette\Forms\IControl $control)
	{
		$this->control = $control;
	}



	/**
	 * @return mixed
	 */
	protected function getValue()
	{
		return $this->control->getValue();
	}



	/**
	 * @param mixed $value
	 * @return bool
	 */
	public static function canHandle($value)
	{
		return $value instanceof Nette\Forms\IControl;
	}

}
