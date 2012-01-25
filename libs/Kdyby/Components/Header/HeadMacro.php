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
use Kdyby\Templates\LatteHelpers;
use Nette;
use Nette\Application\UI;
use Nette\Latte;
use Nette\Utils\Html;
use Nette\Utils\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class HeadMacro extends Nette\Object implements Latte\IMacro
{

	/** @var string[] */
	private $prolog = array();

	/** @var string[] */
	private $epilog = array();



	/**
	 * @param \Nette\Latte\Compiler $compiler
	 *
	 * @return \Kdyby\Components\Header\HeadMacro
	 */
	public static function install(Latte\Compiler $compiler)
	{
		$compiler->addMacro('head', $me = new static($compiler));
		return $me;
	}



	/**
	 * Initializes before template parsing.
	 * @return void
	 */
	public function initialize()
	{

	}



	/**
	 * Finishes template parsing.
	 * @return array(prolog, epilog)
	 */
	public function finalize()
	{
		$prolog = $this->prolog;
		$epilog = $this->epilog;
		$this->prolog = $this->epilog = array();
		return array(
			implode("\n", $prolog) . "\n" . '$_g->kdyby->captureAssets = TRUE;', // inline assets will not be printed out
			implode("\n", $epilog)
		);
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 *
	 * @return bool|string
	 */
	public function nodeOpened(Latte\MacroNode $node)
	{
		$node->openingCode = '<?php Kdyby\Components\Header\HeadMacro::documentBegin(); ?>';
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 *
	 * @return string
	 */
	public function nodeClosed(Latte\MacroNode $node)
	{
		$writer = Latte\PhpWriter::using($node);
		if ($args = LatteHelpers::readArguments($node->tokenizer, $writer)) {
			$this->prolog[] = Code\Helpers::formatArgs('Kdyby\Components\Header\HeadMacro::headArgs($presenter, ?);', array($args));
		}

		$this->epilog[] = '$_documentBody = Kdyby\Components\Header\HeadMacro::documentEnd();';
		$this->epilog[] = 'Kdyby\Components\Header\HeadMacro::headBegin($presenter);';
		$this->epilog[] = '?> '. $this->wrapTags(Nette\Templating\Helpers::optimizePhp($node->content), $writer) . '<?php';
		$this->epilog[] = 'Kdyby\Components\Header\HeadMacro::headEnd($presenter);';
		$this->epilog[] = 'echo $_documentBody;';

		$node->content = NULL;
	}



	/**
	 * @param string $content
	 * @param \Nette\Latte\PhpWriter $writer
	 *
	 * @return string
	 */
	private function wrapTags($content, Latte\PhpWriter $writer)
	{
		return LatteHelpers::wrapTags($content,
			$writer->write('<?php ob_start(); ?>'),
			$writer->write('<?php Kdyby\Components\Header\HeadMacro::tagCaptureEnd($presenter); ?>')
		);
	}



	/**
	 */
	public static function documentBegin()
	{
		ob_start();
	}



	/**
	 */
	public static function documentEnd()
	{
		return ob_get_clean();
	}



	/**
	 * @param \Nette\Application\UI\Presenter $presenter
	 */
	public static function headBegin(UI\Presenter $presenter)
	{
		$head = static::getHead($presenter);
		echo $head->getElement()->startTag();
		$head->renderContent();
	}



	/**
	 * @param \Nette\Application\UI\Presenter $presenter
	 */
	public static function headEnd(UI\Presenter $presenter)
	{
		$head = static::getHead($presenter);
		echo $head->getElement()->startTag();
	}



	/**
	 * @param \Nette\Application\UI\Presenter $presenter
	 */
	public static function tagCaptureEnd(UI\Presenter $presenter)
	{
		$tag = Nette\Utils\Html::el(substr(ob_get_clean(), 1));
		$head = static::getHead($presenter);

		if ($tag->getName() === 'meta') {
			$head->addMeta($tag->attrs);

		} elseif ($tag->getName() === 'link' && $tag->attrs['rel'] === 'shortcut icon') {
			$head->setFavicon($tag);

		} else {
			$head->addTag($tag);
		}
	}



	/**
	 * @param \Nette\ComponentModel\Container $component
	 * @return \Kdyby\Components\Header\HeaderControl
	 */
	private static function getHead(Nette\ComponentModel\Container $component)
	{
		if (!$component->getComponent('head', FALSE)) {
			throw new Kdyby\InvalidStateException('You have to register Kdyby\Components\Header\HeaderControl as presenter component named "head".');
		}

		return $component->getComponent('head');
	}



	/**
	 * @param \Nette\Application\UI\Presenter $presenter
	 * @param array $args
	 */
	public static function headArgs(UI\Presenter $presenter, array $args)
	{
		$head = static::getHead($presenter);
		if (isset($args['title'])) {
			$head->defaultTitle = (array)$args['title'];
		}
	}

}
