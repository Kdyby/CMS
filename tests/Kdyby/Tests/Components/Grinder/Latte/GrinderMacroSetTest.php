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



	/**
	 * @return array
	 */
	public function dataMacros()
	{
		return $this->findInputOutput('files/*.latte', 'output/*.phtml');
	}



	/**
	 * @dataProvider dataMacros
	 *
	 * @param string $input
	 * @param string $output
	 */
	public function testMacro($input, $output)
	{
		$this->parse($input);
		$this->assertLatteMacroEquals($output);
	}

}
