<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Components\Grinder\Latte;

use Kdyby;
use Nette;
use Nette\Latte;
use Nette\Latte\MacroNode;
use Nette\Latte\PhpWriter;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class GrinderMacroSet extends Nette\Latte\Macros\MacroSet
{

	/**
	 * @param \Nette\Latte\Compiler $compiler
	 * @return \Kdyby\Components\Grinder\Latte\GrinderMacroSet
	 */
	public static function install(Latte\Compiler $compiler)
	{
		$me = new static($compiler);
		$me->addMacro('grid', array($me, 'macroGridBegin'), '');
		$me->addMacro('gridHeader', array($me, 'macroHeaderBegin'), array($me, 'macroHeaderEnd'));
		$me->addMacro('gridRow', array($me, 'macroRowBegin'), array($me, 'macroRowEnd'));
		$me->addMacro('gridCell', array($me, 'macroCellBegin'), array($me, 'macroCellEnd'));
		return $me;
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 *
	 * @return string
	 */
	public function macroGridBegin(MacroNode $node, PhpWriter $writer)
	{
		$columns = '$_gridColumns = $_grid->getColumns();';
		if ($node->args !== '') {
			return $writer->write('$_grid = $grid = ' . get_called_class() . '::gridBegin($control[%node.word]);' . $columns);

		} else {
			return $writer->write('$_grid = $grid = ' . get_called_class() . '::gridBegin($control);' . $columns);
		}
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 */
	public function macroHeaderBegin(MacroNode $node, PhpWriter $writer)
	{
		// handles "{gridHeader name /}"
		if ($node->isEmpty = (substr($node->args, -1) === '/')) {
			$node->content = '<?php ' . $writer->write('$_column = $column = $_grid->getColumn(%node.word);') . ' ?><td />';

			// expand <td />, and make it work
			if ($this->expandCell($node, $writer, '$_column->caption')) {
				$node->openingCode = $node->content;
			}

			if ($this->wrapHeaderContent($node)) {
				$node->openingCode = $node->content;
			}

			$node->content = NULL;
		}
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 */
	public function macroHeaderEnd(MacroNode $node, PhpWriter $writer)
	{
		// expands <td />
		$this->expandCell($node, $writer, '$caption');

		if (!$node->args) { // expands <td n:gridHeader /> to columns iterator
			$this->iterateColumns($node, '$caption = $column->caption;');

		} else { // expands <td n:gridHeader="name" />
			$node->openingCode = '<?php '. $writer->write('$_column = $column = $_grid->getColumn(%node.word);') . '$caption = $column->caption; ?>';
			$this->wrapHeaderContent($node);
		}
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 */
	public function macroRowBegin(MacroNode $node, PhpWriter $writer)
	{
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 */
	public function macroRowEnd(MacroNode $node, PhpWriter $writer)
	{
		$node->openingCode = '<?php foreach ($iterator = $_l->its[] = new Nette\Iterators\CachingIterator($_grid) as $item): ?>';
		$node->closingCode = '<?php endforeach; array_pop($_l->its); $iterator = end($_l->its) ?>';
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 */
	public function macroCellBegin(MacroNode $node, PhpWriter $writer)
	{
		// handles "{gridCell name /}"
		if ($node->isEmpty = (substr($node->args, -1) === '/')) {
			$node->content = '<td />';

			// expand <td />, and make it work
			if ($this->expandCell($node, $writer, '$_grid->getColumn(%node.word)->getValue()')) {
				$node->openingCode = $node->content;
				$node->content = NULL;
			}
		}
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 */
	public function macroCellEnd(MacroNode $node, PhpWriter $writer)
	{
		// expands <td />
		$this->expandCell($node, $writer, '$value');

		if (!$node->args) { // expands <td n:gridCell /> to columns iterator
			$this->iterateColumns($node, '$value = $column->getValue();');

		} else { // expands <td n:gridCell="name" />
			$node->openingCode = '<?php ' .
				$writer->write('$_column = $column = $_grid->getColumn(%node.word); $value = $column->getValue();') .
				'?>';
		}
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 *
	 * @return bool
	 */
	private function wrapHeaderContent(MacroNode $node)
	{
		$start = '<?php echo $_column->getSortingControl()->startTag(); ?>';
		$end = '<?php echo $_column->getSortingControl()->endTag(); ?>';
		return $this->createTagContainer($node, $start, $end);
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param string $containerStart
	 * @param string $containerEnd
	 *
	 * @return bool
	 */
	private function createTagContainer(MacroNode $node, $containerStart, $containerEnd)
	{
		if (!$node->htmlNode) {
			return FALSE;
		}

		$tagName = $node->htmlNode->name;
		if ($m = Strings::match($node->content, '~(<' . $tagName . '(?:\s+(?:.*))?>)(.*)(<\\/' . $tagName . '>)~mi')) {
			$content = substr_replace($m[0], $containerStart . $m[2] . $containerEnd, strlen($m[1]), strlen($m[2]));
			$node->content = strtr($node->content, array(
				$m[0] => $content
			));

			return TRUE;
		}

		return FALSE;
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param \Nette\Latte\PhpWriter $writer
	 * @param string $content
	 *
	 * @return bool
	 */
	private function expandCell(MacroNode $node, PhpWriter $writer, $content)
	{
		if ($m = Strings::match(trim($node->content), '~^<([^>\t\n\r ]+)\s*([^>]*)\\/>$~mi')) {
			list($match, $tag) = $m;
			if (($attrs = trim($m[2])) || $node->attrCode) {
				$attrs = ' ' . $attrs;
			}

			// expand "<td n:gridCell />" to "<td>{$value}</td>"
			$content = "<?php echo Nette\\Templating\\Helpers::escapeHtml(" . $writer->write($content) . ", ENT_NOQUOTES) ?>";
			$node->content = strtr($node->content, array(
				$match => "<{$tag}{$attrs}>{$content}</{$tag}>"
			));

			return TRUE;
		}

		return FALSE;
	}



	/**
	 * @param \Nette\Latte\MacroNode $node
	 * @param string $step
	 */
	private function iterateColumns(MacroNode $node, $step = NULL)
	{
		$node->openingCode = '<?php foreach ($iterator = $_l->its[] = ' .
			'new Nette\Iterators\CachingIterator($_gridColumns) as $column): ' . $step . ' ?>';
		$node->closingCode = '<?php endforeach; array_pop($_l->its); $iterator = end($_l->its) ?>';
	}



	/**
	 * @param \Nette\ComponentModel\IComponent $grid
	 *
	 * @throws \Kdyby\InvalidStateException
	 * @return \Kdyby\Components\Grinder\Grid
	 */
	public static function gridBegin(Nette\ComponentModel\IComponent $grid)
	{
		if (!$grid instanceof Kdyby\Components\Grinder\Grid) {
			throw new Kdyby\InvalidStateException("Component " . $grid->getName() . " is not a grid.");
		}

		return $grid;
	}

}
