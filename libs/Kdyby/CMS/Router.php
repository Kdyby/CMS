<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\CMS;

use Kdyby;
use Nette;
use Nette\Http;



/**
 * @author Filip Procházka
 */
class Router extends Nette\Application\Routers\Route
{

	/** @var RouterContext */
	private $context;

	/** @var array */
	private $params = array();



	/**
	 * Context must contain
	 * - languages in $params
	 * - Content\Node service
	 *
	 * @todo Solve secured routes
	 *
	 * @param RouterContext $context
	 */
	public function __construct(RouterContext $context, $prefix = NULL)
	{
		$this->context = $context;

		parent::__construct($prefix . '/[<language [a-z]{2,3}>/]<path .*>[.<extension [a-z0-9.]{2,7}>]', array(
			'presenter' => 'Controller',
			'action' => 'default',
			// todo FILTER_IN for lang
			'language' => array(
				self::VALUE => NULL,
				self::FILTER_IN => callback($this, 'filterInLanguage'),
				self::FILTER_OUT => callback($this, 'filterOutLanguage')
			),
			'path' => array(
				self::VALUE => NULL,
				self::FILTER_IN => callback($this, 'filterInNode'),
				self::FILTER_OUT => callback($this, 'filterOutNode')
			),
			'extension' => array(
				self::VALUE => 'html',
				self::FILTER_IN => callback($this, 'filterInExtension'),
				self::FILTER_OUT => callback($this, 'filterInExtension')
			)
		));
	}



	/**
	 * @param Http\IRequest $httpRequest
	 * @return Nette\Application\Request
	 */
	public function match(Http\IRequest $httpRequest)
	{
		$this->params = array();
		$request = parent::match($httpRequest);
		$request->setParams($this->params + $request->getParams());
		return $request;
	}



	/**
	 * Constructs absolute URL from Request object.
	 * @param  Nette\Application\Request
	 * @param  Nette\Http\Url
	 * @return string|NULL
	 */
	public function constructUrl(Nette\Application\Request $appRequest, Http\Url $refUrl)
	{
		$appRequest = clone $appRequest;
		$params = $appRequest->getParams();
		unset($params['node']);
		$appRequest->setParams($params);
		return parent::constructUrl($appRequest, $refUrl);
	}



	/**
	 * @param string $lang
	 * @return string
	 */
	public function filterInLanguage($lang)
	{
		return rawurldecode($lang);
	}



	/**
	 * @param string $lang
	 * @return string
	 */
	public function filterOutLanguage($lang)
	{
		return $lang;
	}



	/**
	 * @param string $path
	 * @return string
	 */
	public function filterInNode($path)
	{
		$path = trim(rawurldecode($path), '/');
		$this->params['node'] = NULL;
		return $path;
	}



	/**
	 * @param string $path
	 * @return string
	 */
	public function filterOutNode($path)
	{
		return $path;
	}



	/**
	 * @param string $extension
	 * @return string
	 */
	public function filterInExtension($extension)
	{
		return rawurldecode($extension);
	}



	/**
	 * @param string $extension
	 * @return string
	 */
	public function filterOutExtension($extension)
	{
		return $extension;
	}

}