<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Components\Grinder;

use Nette;
use Kdyby;



/**
 * @author Filip Procházka
 */
class GridForm extends \Kdyby\Application\UI\Form
{

	/**
	 */
	protected function configure()
	{
		$this->addContainer('columns');
		$this->addContainer('toolbar');
	}



	/**
	 * @param boolean $need
	 *
	 * @return \Kdyby\Components\Grinder\Grid
	 */
	public function getGrid($need = TRUE)
	{
		return $this->lookup('Kdyby\Components\Grinder\Grid', $need);
	}



	/**
	 * Don't call repetedly
	 *
	 * @param array $ids
	 */
	public function setRecordIds(array $ids)
	{
		$container = $this->addContainer('ids');
		$perPage = $this->getGrid()->getPaginator()->getItemsPerPage();
		for ($index = 0; $index < $perPage; $index++) {
			$container->addHidden($index);
		}
		$container->setDefaults(array_values($ids));
	}



	/**
	 * @return array
	 */
	public function getRecordIds()
	{
		if ($container = $this->getComponent('ids', FALSE)) {
			/** @var \Nette\Forms\Container $container */
			return $container->getValues(TRUE);
		}
		return array();
	}

}
