<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Testing\Application\Routers;

use Kdyby;
use Nette;
use Nette\Http;



/**
 * @author Filip Procházka
 */
class RouterTest extends Kdyby\Testing\TestCase
{

	/** @var Kdyby\CMS\Router */
	private $route;

	/** @var Kdyby\CMS\RouterContext */
	private $context;



	public function setUp()
	{
		$this->context = new Kdyby\CMS\RouterContext;
		$this->route = new Kdyby\CMS\Router($this->context);
	}



	/**
	 * @return array
	 */
	public function getRequests()
	{
		return array(
			array(
				'http://www.kdyby.org/',
				$this->createParams(array()),
			), array(
				'http://www.kdyby.org/cs',
				$this->createParams(array('language' => 'cs')),
			), array(
				'http://www.kdyby.org/cs/',
				$this->createParams(array('language' => 'cs')),
			), array(
				'http://www.kdyby.org/contact',
				$this->createParams(array('path' => 'contact'), NULL),
			), array(
				'http://www.kdyby.org/contact/',
				$this->createParams(array('path' => 'contact'), NULL),
			), array(
				'http://www.kdyby.org/cs/contact',
				$this->createParams(array('language' => 'cs', 'path' => 'contact'), NULL),
			), array(
				'http://www.kdyby.org/cs/contact/',
				$this->createParams(array('language' => 'cs', 'path' => 'contact'), NULL),
			), array(
				'http://www.kdyby.org/articles/kdyby-cms-is-great',
				$this->createParams(array('path' => 'articles/kdyby-cms-is-great'), NULL),
			), array(
				'http://www.kdyby.org/articles/kdyby-cms-is-great/',
				$this->createParams(array('path' => 'articles/kdyby-cms-is-great'), NULL),
			), array(
				'http://www.kdyby.org/cs/articles/kdyby-cms-is-great',
				$this->createParams(array('language' => 'cs', 'path' => 'articles/kdyby-cms-is-great'), NULL),
			), array(
				'http://www.kdyby.org/cs/articles/kdyby-cms-is-great/',
				$this->createParams(array('language' => 'cs', 'path' => 'articles/kdyby-cms-is-great'), NULL),
			)
		);
	}



	/**
	 * @param string $url
	 * @param array $query
	 */
	private function createRequest($url, array $query = array('foo' => 1, 'bar' => 2))
	{
		return new Http\Request(new Nette\Http\UrlScript($url), $query, array(), array(), array(), array(), Http\Request::GET, NULL, NULL);
	}



	/**
	 * @param array $params
	 * @param type $node
	 * @return array
	 */
	private function createParams(array $params, $node = NULL)
	{
		$node = func_num_args()>1 ? array('node' => $node) : array();
		return $node + $params + array('action' => 'default', 'path' => NULL, 'language' => NULL, 'extension' => 'html', 'foo' => 1, 'bar' => 2);
	}



	/**
	 * @dataProvider getRequests
	 * @param string $url
	 * @param array $params
	 */
	public function testMatching($url, array $params)
	{
		$result = $this->route->match($this->createRequest($url));
		$this->assertInstanceOf('Nette\Application\Request', $result);
		$this->assertEquals($params, $result->getParams());
	}



	/**
	 * @dataProvider getRequests
	 * @param string $url
	 * @param array $params
	 */
	public function testConstruction($url, array $params)
	{
		$httpRequest = $this->createRequest($url);
		$appRequest = new Nette\Application\Request(
			'Controller',
			Nette\Application\Request::FORWARD,
			$params,
			array(),
			array()
		);

		$refUrl = new Http\Url($httpRequest->getUrl());
		$refUrl->setPath($httpRequest->getUrl()->getScriptPath());

		$url = $this->route->constructUrl($appRequest, $refUrl);
		$this->assertInternalType('string', $url);
	}

}