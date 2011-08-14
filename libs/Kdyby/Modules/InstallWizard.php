<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Modules;

use Kdyby;
use Kdyby\Loaders\RobotLoader;
use Nette;
use Nette\Caching\Cache;
use Nette\Iterators\Filter;



/**
 * @author Filip Procházka
 */
class InstallWizard extends Nette\Object
{

	const CACHE_NS = 'Kdyby.Modules.Installers';

	/** @var RobotLoader */
	private $robotLoader;

	/** @var Cache */
	private $cache;

	/** @var array of IInstaller */
	private $installers;



	/**
	 * @param RobotLoader $loader
	 */
	public function __construct(RobotLoader $loader, Nette\Caching\IStorage $storage)
	{
		$this->robotLoader = $loader;
		$this->cache = new Cache($storage, self::CACHE_NS);
	}



	/**
	 * @return array of IInstaller
	 */
	public function getInstallers()
	{
		if ($this->installers === NULL) {
			$installersKey = $this->cache->getNamespace() . Cache::NAMESPACE_SEPARATOR . md5('installers');
			$installersCreated = $this->cache->getStorage()->getCreateTime($installersKey);
			$classesIndexCreated = $this->robotLoader->getIndexCreateTime();

			if (!$this->cache->load('installers') || $installersCreated < $classesIndexCreated) {
				$this->cache->save('installers', $this->doSearchInstallers());
			}

			foreach ((array)$this->cache->load('installers') as $installer) {
				$this->installers[] = new $installer;
			}
		}

		return (array)$this->installers;
	}



	/**
	 * @param string $module
	 * @return array of IInstaller
	 */
	public function getModuleInstallers($module)
	{
		$installers = new \ArrayIterator($this->getInstallers());
		$moduleInstallers = new Filter($installers, function (Filter $iterator) use ($module) {
			return $iterator->current()->getModuleName() === $module;
		});

		return iterator_to_array($moduleInstallers);
	}



	/**
	 * @return array
	 */
	private function doSearchInstallers()
	{
		return $this->robotLoader->createIndexFilter()
			->implementsInterface('Kdyby\Modules\IInstaller')
			->isInstantiable()
			->getResult();
	}

}