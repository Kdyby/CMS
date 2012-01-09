<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Components\Header;

use Kdyby;
use Kdyby\Assets\FormulaeManager;
use Nette;
use Nette\Http;
use Nette\Application\Application;
use Nette\Utils\Html;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class HeaderControl extends Kdyby\Application\UI\Control
{

	/** @var string */
	public $defaultTitle;

	/** @var bool */
	public $titleReverse = FALSE;

	/** @var string */
	public $titleSeparator = '-';

	/** @var array */
	private $title = array();

	/** @var \Nette\Utils\Html */
	private $headEl;

	/** @var \Nette\Utils\Html */
	private $titleEl;

	/** @var \Kdyby\Assets\FormulaeManager */
	private $formulaeManager;

	/** @var string */
	private $httpRequest;

	/** @var \Nette\Utils\Html[] */
	private $htmlTags = array();

	/** @var array */
	private $meta = array(
		'content-type' => array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=utf-8'),
		'robots' => array('name' => 'robots', 'content' => 'noindex')
	);



	/**
	 * @param \Nette\Application\Application $application
	 * @param \Kdyby\Assets\FormulaeManager $formulaeManager
	 * @param \Nette\Http\Request $httpRequest
	 */
	public function __construct(Application $application, FormulaeManager $formulaeManager, Http\Request $httpRequest)
	{
		parent::__construct();

		$this->formulaeManager = $formulaeManager;
		$this->httpRequest = $httpRequest;
		$application->onShutdown[] = function () use ($formulaeManager) {
			$formulaeManager->publish();
		};

		$this->headEl = Html::el('head');
		$this->titleEl = Html::el('title');
	}



	/**
	 * @return string
	 */
	protected function getBaseUrl()
	{
		return rtrim($this->httpRequest->getUrl()->getBaseUrl(), '/');
	}



	/**
	 * @param string $path
	 * @return string
	 */
	protected function absolutePath($path)
	{
		if ($path[0] !== '/' && !Nette\Utils\Validators::isUrl($path)) {
			$path = $this->getBaseUrl() . '/' . ltrim($path, '/');
		}

		return $path;
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
	 * @return \Kdyby\Components\HeaderControl
	 */
	public function setTitle($title, $reverse = NULL, $separator = NULL)
	{
		if (is_bool($reverse)) {
			$this->titleReverse = $reverse;
		}

		if ($separator !== NULL) {
			$this->titleSeparator = $separator;
		}

		$this->title = (array)$title;
		return $this;
	}



	/**
	 * @param string $title
	 * @return \Kdyby\Components\HeaderControl
	 */
	public function addTitle($title)
	{
		$this->title = array_merge($this->title, array($title));
		return $this;
	}



	/**
	 * @return \Nette\Utils\Html
	 */
	public function getTitle()
	{
		$titleEl = clone $this->titleEl;
		$title = $this->titleReverse ? array_reverse($this->title) : $this->title;
		$titleEl->setText(implode(' ' . $this->titleSeparator . ' ', $title));
		return $titleEl;
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
	 * @param \Nette\Utils\Html $favicon
	 * @return \Nette\Utils\Html
	 */
	public function setFavicon($favicon)
	{
		$faviconEl = Html::el('link')
			->rel('shortcut icon')
			->href($this->absolutePath($favicon))
			->type('image/x-icon');
		return $this->addTag($faviconEl);
	}



	/**
	 * @param \Nette\Utils\Html|string $tag
	 * @return \Nette\Utils\Html
	 */
	public function addTag($tag)
	{
		if (!$tag instanceof Html) {
			$tag = Html::el()->setHtml($tag);
		}

		return $this->htmlTags[] = $tag;
	}



	/**
	 * @param \Nette\Utils\Html $head
	 *
	 * @return \Nette\Utils\Html
	 */
	protected function createHead(Html $head)
	{
		// meta
		foreach ($this->meta as $meta) {
			$head->add(Html::el('meta')->addAttributes($meta));
		}

		// title
		$head->add($this->getTitle());

		// other tags
		foreach ($this->htmlTags as $tag) {
			$head->add(clone $tag);
		}

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

		return $head;
	}



	/**
	 */
	public function render()
	{
		echo $this->createHead(clone $this->headEl);
	}



	/**
	 */
	public function renderContent()
	{
		echo $this->createHead(Html::el());
	}

}
