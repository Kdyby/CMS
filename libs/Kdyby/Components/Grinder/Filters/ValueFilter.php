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
class ValueFilter extends Kdyby\Components\Grinder\QueryFilter
{

	/** @var mixed */
	public $value;



	/**
	 * @param mixed $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}



	/**
	 * @return mixed
	 */
	protected function getValue()
	{
		return $this->value;
	}



	/**
	 * @param mixed $value
	 * @return bool
	 */
	public static function canHandle($value)
	{
		return TRUE;
	}

}
