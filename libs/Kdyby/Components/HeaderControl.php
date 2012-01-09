<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */
 
namespace Kdyby\Components;

use Kdyby;
use Kdyby\Assets\FormulaeManager;
use Nette;
use Nette\Application\Application;
use Nette\Utils\Html;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class HeaderControl extends Kdyby\Application\UI\Control
{

	/** @var \Nette\Utils\Html */
	private $headEl;

	/** @var \Nette\Utils\Html */
	private $titleEl;

	/** @var \Kdyby\Assets\FormulaeManager */
	private $formulaeManager;

	/** @var array */
	private $meta = array(
		'content-type' => array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=utf-8'),
		'robots' => array('name' => 'robots', 'content' => 'noindex')
	);



	/**
	 * @param \Nette\Application\Application $application
	 * @param \Kdyby\Assets\FormulaeManager $formulaeManager
	 */
	public function __construct(Application $application, FormulaeManager $formulaeManager)
	{
		parent::__construct();

		$this->formulaeManager = $formulaeManager;
		$application->onShutdown[] = function () use ($formulaeManager) {
			$formulaeManager->publish();
		};

		$this->headEl = Html::el('head');
		$this->titleEl = Html::el('title');
	}



	/**
	 * @return \Nette\Utils\Html
	 */
	public function getHeadPrototype()
	{
		return $this->headEl;
	}



	/**
	 * @param array|string $title
	 * @param bool $reverse
	 * @param string $separator
	 */
	public function setTitle($title, $reverse = FALSE, $separator = '-')
	{
		if (is_array($title)) {
			$title = $reverse ? array_reverse($title) : $title;
			$title = implode(' ' . $separator . ' ', $title);
		}

		$this->titleEl->setText($title);
	}



	/**
	 * @param array $meta
	 */
	public function addMeta(array $meta)
	{
		if (!isset($meta['name']) && !isset($meta['http-equiv'])) {
			throw new Kdyby\InvalidArgumentException('Meta must contain either "name" or "http-equiv".');
		}

		$key = isset($meta['name']) ? $meta['name'] : $meta['http-equiv'];
		$this->meta[strtolower($key)] = $meta;
	}



	/**
	 */
	public function render()
	{
		$head = clone $this->headEl;

		// meta
		foreach ($this->meta as $meta) {
			$head->add(Html::el('meta')->addAttributes($meta));
		}

		// title
		$head->add(clone $this->titleEl);

		// styles
		$fm = $this->formulaeManager;
		foreach ($fm->getAssets(FormulaeManager::TYPE_STYLESHEET) as $style) {
			$el = Html::el('link')->href($style['src'])->type('text/css');
			$el->rel(isset($style['rel']) ? $style['rel'] : 'stylesheet');
			$el->media(isset($style['media']) ? $style['media'] : 'screen,projection,tv');
			$head->add($el);
		}

		// scripts
		foreach ($fm->getAssets(FormulaeManager::TYPE_JAVASCRIPT) as $script) {
			$head->add(Html::el('script')->src($script['src'])->type('text/javascript'));
		}

		echo $head;
	}

}
