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
class CallbackFilter extends Kdyby\Components\Grinder\QueryFilter
{
	/** @var \Nette\Callback */
	public $callback;



	/**
	 * @param callback $callback
	 */
	public function __construct($callback)
	{
		$this->callback = callback($callback);
	}



	/**
	 * @return mixed
	 */
	protected function getValue()
	{
		/** @var \Kdyby\Components\Grinder\Grid $grid */
		$grid = $this->filters->getGrid();
		return $this->callback->invoke($this, $grid->getColumn($this->column));
	}



	/**
	 * @param mixed $value
	 * @return bool
	 */
	public static function canHandle($value)
	{
		return is_callable($value);
	}

}
