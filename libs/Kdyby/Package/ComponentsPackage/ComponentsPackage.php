<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Package\ComponentsPackage;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class ComponentsPackage extends Kdyby\Packages\Package
{

	/**
	 * @param \Nette\Config\Configurator $config
	 * @param \Nette\Config\Compiler $compiler
	 * @param \Kdyby\Packages\PackagesContainer $packages
	 */
	public function compile(Nette\Config\Configurator $config, Nette\Config\Compiler $compiler, Kdyby\Packages\PackagesContainer $packages)
	{
		// Kdyby Components
		$compiler->addExtension('kc', new DI\ComponentsExtension);
	}

}
