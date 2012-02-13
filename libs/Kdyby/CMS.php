<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby;

use Nette;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
final class CMS
{

	const NAME = 'Kdyby Component Management System';
	const VERSION = '8.1a';
	const REVISION = '$WCREV$ released on $WCDATE$';



	/**
	 * @throws Nette\StaticClassException
	 */
	final public function __construct()
	{
		throw new Nette\StaticClassException;
	}

}
