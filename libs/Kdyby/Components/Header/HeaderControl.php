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

	/**
	 * @var string
	 */
	public $defaultTitle;

	/**
	 * @var bool
	 */
	public $titleReverse = FALSE;

	/**
	 * @var string
	 */
	public $titleSeparator = '-';

	/**
	 * @var array
	 */
	private $title = array();

	/**
	 * @var \Nette\Utils\Html
	 */
	private $headEl;

	/**
	 * @var \Nette\Utils\Html
	 */
	private $titleEl;

	/**
	 * @var \Kdyby\Extension\Assets\FormulaeManager
	 */
	private $formulaeManager;

	/**
	 * @var string
	 */
	private $httpRequest;

	/**
	 * @var \Nette\Utils\Html[]
	 */
	private $htmlTags = array();

	/**
	 * @var array
	 */
	private $meta = array(
		'content-type' => array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=utf-8'),
		// 'robots' => array('name' => 'robots', 'content' => 'noindex')
	);

	/**
	 * @var \Kdyby\Application\UI\Presenter
	 */
	private $parent;

	/**
	 * @var array
	 */
	private $renderedAssets = array();

	/**
	 * @var array
	 */
	private $capturedAssets = array();

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var bool
	 */
	private $published = FALSE;



	/**
	 * @param \Nette\Application\Application $application
	 * @param \Kdyby\Extension\Assets\FormulaeManager $formulaeManager
	 * @param \Nette\Http\Request $httpRequest
	 */
	public function __construct(Application $application, FormulaeManager $formulaeManager, Http\Request $httpRequest)
	{
		$this->formulaeManager = $formulaeManager;
		$this->httpRequest = $httpRequest;

		$this->headEl = Html::el('head');
		$this->titleEl = Html::el('title');
	}



	/**
	 * @return \Kdyby\Extension\Assets\FormulaeManager
	 */
	public function getFormulaeManager()
	{
		return $this->formulaeManager;
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

		return $this->addTag(Html::el('link', array(
			'rel' => 'shortcut icon',
			'href' => $this->absolutePath($favicon),
			'type' => 'image/x-icon'
		)));
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
	 * @param string $type
	 * @param string $source
	 */
	public function addAssetSource($type, $source)
	{
		$this->capturedAssets[$type][] = $source;
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
		if (!$this->published) {
			$this->formulaeManager->publish();
			$this->published = TRUE;
		}

		if (!empty($this->renderedAssets[FormulaeManager::TYPE_STYLESHEET])) {
			return $head;
		}

		$fm = $this->formulaeManager;
		foreach ($fm->getAssets(FormulaeManager::TYPE_STYLESHEET) as $style) {
			$head->add(Html::el('link', array(
				'href' => $style['src'],
				'type' => 'text/css',
				'rel' => isset($style['rel']) ? $style['rel'] : 'stylesheet',
				'media' => isset($style['media']) ? $style['media'] : 'screen,projection,tv'
			)));
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
		if (!$this->published) {
			$this->formulaeManager->publish();
			$this->published = TRUE;
		}

		if (!empty($this->renderedAssets[FormulaeManager::TYPE_JAVASCRIPT])) {
			return $head;
		}

		$fm = $this->formulaeManager;
		foreach ($fm->getAssets(FormulaeManager::TYPE_JAVASCRIPT) as $script) {
			$head->add(Html::el('script', array(
				'src' => $script['src'],
				'type' => 'text/javascript'
			)));
		}

		// inline assets captured from template should be executed at the end
		if (isset($this->capturedAssets['js'])) {
			foreach ($this->capturedAssets['js'] as $inlineJs) {
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
