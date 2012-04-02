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
use Kdyby\Extension\Assets\FormulaeManager;
use Nette;
use Nette\Http;
use Nette\Application\Application;
use Nette\Utils\Html;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class HeaderControl extends Nette\Object implements Nette\ComponentModel\IComponent
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

	/** @var \Kdyby\Extension\Assets\FormulaeManager */
	private $formulaeManager;

	/** @var string */
	private $httpRequest;

	/** @var \Nette\Utils\Html[] */
	private $htmlTags = array();

	/** @var array */
	private $meta = array(
		'content-type' => array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=utf-8'),
		// 'robots' => array('name' => 'robots', 'content' => 'noindex')
	);

	/** @var \Kdyby\Application\UI\Presenter */
	private $parent;

	/** @var array */
	private $renderedAssets = array();

	/** @var string */
	private $name;



	/**
	 * @param \Nette\Application\Application $application
	 * @param \Kdyby\Extension\Assets\FormulaeManager $formulaeManager
	 * @param \Nette\Http\Request $httpRequest
	 */
	public function __construct(Application $application, FormulaeManager $formulaeManager, Http\Request $httpRequest)
	{
		$this->formulaeManager = $formulaeManager;
		$this->httpRequest = $httpRequest;
		$application->onShutdown[] = function () use ($formulaeManager) {
			/** @var \Kdyby\Extension\Assets\FormulaeManager $formulaeManager */
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
	 *
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
	 *
	 * @return \Kdyby\Components\Header\HeaderControl
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
	 * @return \Kdyby\Components\Header\HeaderControl
	 */
	public function addTitle($title)
	{
		if (!$this->title) {
			$this->title = (array)$this->defaultTitle;
		}

		$this->title = array_merge($this->title, (array)$title);
		return $this;
	}



	/**
	 * @return \Nette\Utils\Html
	 */
	public function getTitle()
	{
		$titleEl = clone $this->titleEl;
		$title = (array)($this->title ?: $this->defaultTitle);
		$title = $this->titleReverse ? array_reverse($title) : $title;
		return $titleEl->setText(implode(' ' . $this->titleSeparator . ' ', $title));
	}



	/**
	 * @param array $meta
	 *
	 * @throws \Kdyby\InvalidArgumentException
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
	 * @param \Nette\Utils\Html|string $favicon
	 *
	 * @return \Nette\Utils\Html
	 */
	public function setFavicon($favicon)
	{
		if ($favicon instanceof Html) {
			return $this->addTag($favicon);
		}

		$faviconEl = Html::el('link')
			->rel('shortcut icon')
			->href($this->absolutePath($favicon))
			->type('image/x-icon');
		return $this->addTag($faviconEl);
	}



	/**
	 * @param \Nette\Utils\Html|string $tag
	 *
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
	protected function buildHead(Html $head)
	{
		// meta
		foreach ($this->meta as $meta) {
			$head->add(Html::el('meta')->addAttributes($meta));
		}

		// title
		$head->add($this->getTitle());

		// other html tags
		foreach ($this->htmlTags as $tag) {
			$head->add(clone $tag);
		}

		// assets
		$this->buildStyles($head);
		$this->buildScripts($head);

		return $head;
	}



	/**
	 * @param \Nette\Utils\Html $head
	 *
	 * @return \Nette\Utils\Html
	 */
	protected function buildStyles(Html $head)
	{
		if (!empty($this->renderedAssets[FormulaeManager::TYPE_STYLESHEET])) {
			return $head;
		}

		$fm = $this->formulaeManager;
		foreach ($fm->getAssets(FormulaeManager::TYPE_STYLESHEET) as $style) {
			$el = Html::el('link')->href($style['src'])->type('text/css');
			$el->rel(isset($style['rel']) ? $style['rel'] : 'stylesheet');
			$el->media(isset($style['media']) ? $style['media'] : 'screen,projection,tv');
			$head->add($el);
		}

		$this->renderedAssets[FormulaeManager::TYPE_STYLESHEET] = TRUE;
		return $head;
	}



	/**
	 * @param \Nette\Utils\Html $head
	 *
	 * @return \Nette\Utils\Html
	 */
	protected function buildScripts(Html $head)
	{
		if (!empty($this->renderedAssets[FormulaeManager::TYPE_JAVASCRIPT])) {
			return $head;
		}

		$fm = $this->formulaeManager;
		foreach ($fm->getAssets(FormulaeManager::TYPE_JAVASCRIPT) as $script) {
			$head->add(Html::el('script')->src($script['src'])->type('text/javascript'));
		}

		// inline assets captured from template should be executed at the end
		$globals = $this->parent->getTemplate()->_g;
		if (isset($globals->kdyby->assets['js'])) {
			foreach ($globals->kdyby->assets['js'] as $inlineJs) {
				$head->add(Html::el()->setHtml($inlineJs));
			}
		}

		$this->renderedAssets[FormulaeManager::TYPE_JAVASCRIPT] = TRUE;
		return $head;
	}



	/**
	 * @return \Nette\Utils\Html
	 */
	public function getElementPrototype()
	{
		return $this->headEl;
	}



	/**
	 * @return \Nette\Utils\Html
	 */
	public function getElement()
	{
		return clone $this->headEl;
	}



	/**
	 * Renders the whole header contained in <header> tag
	 */
	public function render()
	{
		echo $this->buildHead(clone $this->headEl);
	}



	/**
	 * Renders the while header without the <header> tag
	 */
	public function renderContent()
	{
		echo $this->buildHead(Html::el());
	}



	/**
	 * Renders only stylesheet elements
	 */
	public function renderStyles()
	{
		echo $this->buildStyles(Html::el());
	}



	/**
	 * Renders only javascript elements
	 */
	public function renderScripts()
	{
		echo $this->buildScripts(Html::el());
	}


	/******************* Nette\ComponentModel\IComponent *********************/


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * Returns the container if any.
	 * @return \Nette\ComponentModel\IContainer|NULL
	 */
	public function getParent()
	{
		return $this->parent;
	}



	/**
	 * Sets the parent of this component.
	 *
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 */
	public function setParent(Nette\ComponentModel\IContainer $parent = NULL, $name = NULL)
	{
		$this->parent = $parent;
		$this->name = $name;
	}

}
