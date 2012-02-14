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
use Nette\Application\UI\PresenterComponent;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class ParameterFilter extends Kdyby\Components\Grinder\QueryFilter
{

	/** @var \Nette\Application\UI\PresenterComponent */
	public $component;

	/** @var string */
	public $parameter;



	/**
	 * @param array $input
	 */
	public function __construct(array $input)
	{
		list($component, $parameter) = $input;
		if (!$component instanceof PresenterComponent) {
			throw new Kdyby\InvalidArgumentException('Item #0 of input array must be instanceof Nette\Application\UI\PresenterComponent, ' . Kdyby\Tools\Mixed::getType($component) . " given.");

		} elseif (!is_string($parameter)) {
			throw new Kdyby\InvalidArgumentException('Item #1 of input array must be string, ' . Kdyby\Tools\Mixed::getType($component) . " given.");
		}

		$this->component = $component;
		$this->parameter = $parameter;
	}



	/**
	 * @return mixed
	 */
	protected function getValue()
	{
		return $this->component->getParameter($this->parameter, NULL);
	}



	/**
	 * @param mixed $value
	 * @return bool
	 */
	public static function canHandle($value)
	{
		return is_array($value) && isset($value[0], $value[1])
			&& $value[0] instanceof PresenterComponent
			&& is_string($value[1]);
	}

}
