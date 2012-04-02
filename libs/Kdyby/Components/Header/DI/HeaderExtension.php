<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Components\Header\DI;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class HeaderExtension extends Nette\Config\CompilerExtension
{

	/**
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('control'))
			->setClass('Kdyby\Components\Header\HeaderControl', array('@application', '@assets.formulaeManager'));

		$builder->getDefinition('nette.latte')
			->addSetup('Kdyby\Components\Header\HeadMacro::install(?->compiler)', array('@self'));
	}

}
