<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby;




/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
final class CMS
{

	const NAME = 'Kdyby Component Management System';
	const VERSION = '8.1a';
	const REVISION = '$WCREV$ released on $WCDATE$';



	/**
	 * @throws \Kdyby\StaticClassException
	 */
	final public function __construct()
	{
		throw new StaticClassException;
	}



	/**
	 * @return array
	 */
	public static function getDefaultPackages()
	{
		return array(
			'Kdyby\Package\ComponentsPackage\ComponentsPackage',
			'Kdyby\Package\CmsPackage\CmsPackage',
		);
	}



	/**
	 * @return \Kdyby\Packages\PackagesList
	 */
	public static function createPackagesList()
	{
		return new Packages\PackagesList(
			array_merge(Framework::getDefaultPackages(), static::getDefaultPackages())
		);
	}

}
