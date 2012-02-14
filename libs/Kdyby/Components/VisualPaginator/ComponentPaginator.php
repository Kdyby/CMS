<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Components\VisualPaginator;

use Kdyby\Application\UI\Control;



/**
 * @author David Grudl
 * @author Filip Procházka
 */
class ComponentPaginator extends Control
{

	/** @var \Kdyby\Components\VisualPaginator\Paginator */
	private $paginator;



	/**
	 * @return \Kdyby\Components\VisualPaginator\Paginator
	 */
	public function getPaginator()
	{
		if ($this->paginator === NULL) {
			$this->paginator = new Paginator;
		}

		return $this->paginator;
	}



	/**
	 * Renders paginator.
	 * @return void
	 */
	public function render()
	{
		echo $this->__toString();
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		$this->template->steps = $this->getPaginator()->getPagesListFriendly();
		$this->template->paginator = $this->getPaginator();

		if($this->template->getFile() === NULL){
			$this->template->setFile(dirname(__FILE__) . '/template.phtml');
		}

		return (string)$this->template;
	}



	/**
	 * @param string $page
	 *
	 * @return \Kdyby\Components\VisualPaginator\ComponentPaginator
	 */
	public function setPage($page)
	{
		$this->getPaginator()->page = $page;
		return $this;
	}



	/**
	 * @param string $file
	 *
	 * @return \Kdyby\Components\VisualPaginator\ComponentPaginator
	 */
	public function setTemplateFile($file)
	{
		$this->getTemplate()->setFile($file);
		return $this;
	}

}
