<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Tests\Components\Grinder\Latte;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class GrinderMacroSetTest extends Kdyby\Tests\LatteTestCase
{

	public function setup()
	{
		$this->installMacro('Kdyby\Components\Grinder\Latte\GrinderMacroSet::install');
	}



	public function testMacroGrid_withoutName()
	{
		$this->parse(__DIR__ . '/files/Grid.macro.withoutName.latte');
		$this->assertLatteMacroEquals(__DIR__ . '/output/Grid.macro.withoutName.phtml');
	}



	public function testMacroGrid_withName()
	{
		$this->parse(__DIR__ . '/files/Grid.macro.withName.latte');
		$this->assertLatteMacroEquals(__DIR__ . '/output/Grid.macro.withName.phtml');
	}

}
