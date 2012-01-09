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
use Nette\Templating\Template;
use Nette\Utils\Html;
use Nette\Utils\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class HeadMacro extends Nette\Object implements Latte\IMacro
{

	/** @var string[] */
	private $prolog = array();



	/**
	 * @param \Nette\Latte\Parser $parser
	 */
	public static function install(Latte\Parser $parser)
	{
		$parser->addMacro('head', new static());
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
		return array(implode("\n", $this->prolog));
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 *
	 * @return bool|string
	 */
	public function nodeOpened(Latte\MacroNode $node)
	{
		return Latte\PhpWriter::using($node)
			->write('<?php Kdyby\Components\Header\HeadMacro::headBegin($presenter); ?>');
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 *
	 * @return string
	 */
	public function nodeClosed(Latte\MacroNode $node)
	{
		$writer = Latte\PhpWriter::using($node);
		$node->content = $this->wrapTags(Template::optimizePhp($node->content), $writer);

		if ($args = LatteHelpers::readArguments($node->tokenizer, $writer)) {
			$this->prolog[] = Code\Helpers::formatArgs('Kdyby\Components\Header\HeadMacro::headArgs($presenter, ?);', array($args));
		}

		return $writer->write('<?php Kdyby\Components\Header\HeadMacro::headEnd($presenter); ?>');
	}



	/**
	 * @param string $content
	 *
	 * @return string
	 */
	private function wrapTags($content, Latte\PhpWriter $writer)
	{
		$code = NULL;
		foreach (LatteHelpers::splitPhp($content) as $item) {
			if (substr($item, 0, 5) === '<?php') {
				$code .= $item;
				continue;
			}
			$code .= Nette\Utils\Strings::replace($item, array(
				'~<([^<\s]+)\s+~' => $writer->write('<?php ob_start(); ?>') . '<\\1 ',
				'~\s+/>~' => " />" . $writer->write('<?php Kdyby\Components\Header\HeadMacro::tagCaptureEnd($presenter); ?>'),
			));
		}
		return $code;
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
