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
		$me = new static($compiler);
		$compiler->addMacro('head', $me);

		$compiler->addMacro('javascript', $me);
		$compiler->addMacro('js', $me);

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
			implode("\n", $prolog),
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
		if (in_array($node->name, array('js', 'javascript'))) {
			if (($node->data->inline = empty($node->args)) && $node->htmlNode) {
				$node->data->type = in_array($node->name, array('js', 'javascript')) ? 'js' : 'css';
				$node->openingCode = '<?php ob_start(); ?>';
				return;

			} else {
				return FALSE;
			}
		}

		$node->openingCode = '<?php Kdyby\Components\Header\HeadMacro::documentBegin(); ?>';
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 *
	 * @return string
	 */
	public function nodeClosed(Latte\MacroNode $node)
	{
		if (!empty($node->data->inline)) {
			$node->closingCode = '<?php ' . get_called_class() . '::tagCaptureEnd($presenter); ?>';
			return;
		}

		$class = get_called_class();
		$writer = Latte\PhpWriter::using($node);
		if ($args = LatteHelpers::readArguments($node->tokenizer, $writer)) {
			$this->prolog[] = Code\Helpers::formatArgs($class . '::headArgs($presenter, ?);', array($args));
		}

		$this->epilog[] = '$_documentBody = ' . $class . '::documentEnd();';
		$this->epilog[] = $class . '::headBegin($presenter); ?>';
		$this->epilog[] = $this->wrapTags($node->content, $writer);
		$this->epilog[] = '<?php ' . $class . '::headEnd($presenter);';
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
		return LatteHelpers::wrapTags(Nette\Templating\Helpers::optimizePhp($content),
			$writer->write('<?php ob_start(); ?>'),
			$writer->write('<?php ' . get_called_class() . '::tagCaptureEnd($presenter); ?>')
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
		echo $head->getElement()->endTag();
	}



	/**
	 * @param \Nette\Application\UI\Presenter $presenter
	 */
	public static function tagCaptureEnd(UI\Presenter $presenter)
	{
		$content = ob_get_clean();
		$tag = Nette\Utils\Html::el(substr($content, 1, ($i = strpos($content, '>')) ? $i-1 : NULL));
		$head = static::getHead($presenter);

		if ($tag->getName() === 'meta') {
			$head->addMeta($tag->attrs);

		} elseif ($tag->getName() === 'link' && $tag->attrs['rel'] === 'shortcut icon') {
			$head->setFavicon($tag);

		} elseif ($tag->getName() === 'script') {
			$head->addAssetSource('js', $content);

		} else {
			$head->addTag($tag);
		}
	}



	/************************ Helpers ************************/


	/**
	 * @param \Nette\Application\UI\PresenterComponent $control
	 * @return \Kdyby\Components\Header\HeaderControl
	 * @throws \Kdyby\InvalidStateException
	 */
	private static function getHead(Nette\Application\UI\PresenterComponent $control)
	{
		/** @var \Nette\Application\UI\Presenter $presenter */
		$presenter = $control->getPresenter();
		$components = $presenter->getComponents(FALSE, 'Kdyby\Components\Header\HeaderControl');
		if (!$headerControl = iterator_to_array($components)) {
			throw new Kdyby\InvalidStateException(
				'Please register Kdyby\Components\Header\HeaderControl as component in presenter.' .
				'If you have the component registered and this error keeps returning, try to instantiate it manually.'
			);
		}

		return reset($headerControl);
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
