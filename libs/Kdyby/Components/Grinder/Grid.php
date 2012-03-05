<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Components\Grinder;

use Doctrine;
use Kdyby;
use Kdyby\Application\UI\Presenter;
use Kdyby\Components\VisualPaginator\Paginator;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\Forms\EntityContainer;
use Nette;
use Nette\Application\Responses\JsonResponse;
use Nette\Utils\Html;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka
 *
 * @property-write int $itemsPerPage
 * @property-read object $current
 * @property-read int $currentIndex
 * @property \Kdyby\Components\Grinder\GridFilters|\Kdyby\Components\Grinder\GridFiltersFluent[] $filters
 */
class Grid extends Kdyby\Application\UI\Control implements \IteratorAggregate, \Countable
{

	/** @persistent int */
	public $page = 1;

	/** @persistent array */
	public $sort = array();

	/** @var bool */
	public $multiSort = FALSE;

	/** @var bool  */
	public $disableSorting = FALSE;

	/** @var bool */
	public $rememberState = FALSE;

	/** @var string|\Nette\Utils\Html */
	public $emptyResultMessage = "We are sorry, but no results were found.";

	/** @var \Kdyby\Doctrine\QueryBuilder */
	private $queryBuilder;

	/** @var \Kdyby\Components\Grinder\Column[] */
	private $columns = array();

	/** @var \Kdyby\Components\Grinder\GridIterator */
	private $iterator;

	/** @var int */
	private $index = 0;

	/** @var object */
	private $record;

	/** @var \Nette\Http\SessionSection|\stdClass */
	private $session;

	/** @var \Kdyby\Components\Grinder\GridFilters|\Kdyby\Components\Grinder\GridFiltersFluent[] */
	private $filters;

	/** @var \Kdyby\Components\VisualPaginator\Paginator */
	private $paginator;



	/**
	 * @param \Kdyby\Doctrine\QueryBuilder $queryBuilder
	 * @param \Kdyby\Doctrine\Registry $doctrine
	 */
	public function __construct(QueryBuilder $queryBuilder, Kdyby\Doctrine\Registry $doctrine)
	{
		parent::__construct();

		$this->addComponent(new GridForm($doctrine), 'form');
		$this->paginator = new Paginator;
		$this->paginator->itemsPerPage = 20;
		$this->filters = new GridFilters($this);
		$this->queryBuilder = $queryBuilder;

		// configure columns
		$this->configureEditing();
	}



	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	protected function getEntityManager()
	{
		return $this->queryBuilder->getEntityManager();
	}



	/**
	 * @return \Kdyby\Doctrine\Mapping\ClassMetadata
	 */
	protected function getClass()
	{
		$class = $this->queryBuilder->getRootEntity();
		return $this->getEntityManager()->getClassMetadata($class);
	}



	/**
	 * @return \Kdyby\Doctrine\Dao
	 */
	public function getRepository()
	{
		$class = $this->queryBuilder->getRootEntity();
		return $this->getEntityManager()->getRepository($class);
	}



	/********************* Construction *********************/



	/**
	 * @param \Nette\ComponentModel\Container $obj
	 */
	protected function attached($obj)
	{
		if (!$obj instanceof Nette\Application\IPresenter) {
			parent::attached($obj);
			return;
		}

		// macros
		$context = $this->getPresenter()->getContext();
		$this->setTemplateConfigurator($context->kdyby->templateConfigurator);

		// load state
		parent::attached($obj);
		$this->paginator->setPage($this->page);

		// configure
		$this->configure($this->getPresenter());
		$this->configureFilters($this->getPresenter());

		// validate sorting
		if ($this->disableSorting) {
			$this->sort = array();

		} elseif (!$this->multiSort && count($this->sort) > 1) {
			$this->sort = array();
		}

		foreach ($this->sort as $column => $type) {
			if (!$this->isValidSorting($column, $type)) {
				unset($this->sort[$column]);
			}
		}
	}



	/**
	 * Gets called on the right time for adding columns and actions
	 *
	 * @param \Kdyby\Application\UI\Presenter $presenter
	 */
	protected function configure(Presenter $presenter)
	{
	}



	/**
	 * Gets called in construction time.
	 * When receiving signal, the rules must be already set
	 */
	protected function configureEditing()
	{
	}


	/********************* State manipulation *********************/



	/**
	 * @param array $params
	 */
	public function loadState(array $params)
	{
		if ($this->rememberState && $session = $this->getStateSession()) {
			if (isset($session->params)) {
				foreach ($session->params as $key => $value) {
					$params[$key] = $value;
				}
			}
		}

		parent::loadState($params);
	}



	/**
	 * @param array $params
	 * @param \Nette\Application\UI\PresenterComponentReflection $reflection
	 */
	public function saveState(array & $params, $reflection = NULL)
	{
		parent::saveState($params, $reflection);

		if ($this->rememberState && $session = $this->getStateSession()) {
			$session->params = $params;
		}
	}



	/**
	 * @todo: di
	 *
	 * @return \Nette\Http\SessionSection|\stdClass
	 */
	protected function getStateSession()
	{
		if (!$presenter = $this->getPresenter()) {
			return NULL;
		}

		$id = $presenter->getName(TRUE) . ':' . $this->getUniqueId();
		return $presenter->getSession(__CLASS__ . '/' . $id);
	}



	/********************* Filters *********************/



	/**
	 * @return \Kdyby\Components\Grinder\GridFilters|\Kdyby\Components\Grinder\GridFiltersFluent[]
	 */
	public function getFilters()
	{
		return $this->filters;
	}



	/**
	 * @param \Kdyby\Application\UI\Presenter $presenter
	 */
	protected function configureFilters(Presenter $presenter)
	{
	}


	/********************* Columns *********************/


	/**
	 * @param string $name
	 * @param string|\Nette\Utils\Html $caption
	 *
	 * @return \Kdyby\Components\Grinder\Column
	 */
	public function addColumn($name, $caption = NULL)
	{
		$column = new Column($this, $name);
		$column->caption = $caption;
		return $this->attachColumn($column);
	}



	/**
	 * @param string $name
	 * @param \Nette\Forms\IControl $control
	 * @param string|\Nette\Utils\Html $caption
	 *
	 * @return \Kdyby\Components\Grinder\Columns\FormColumn
	 */
	public function addFormColumn($name, Nette\Forms\IControl $control, $caption = NULL)
	{
		$column = new Columns\FormColumn($this, $name, $control);
		$column->caption = $caption;
		return $this->attachColumn($column);
	}



	/**
	 * @param string $name
	 * @param string|\Nette\Utils\Html $caption
	 *
	 * @return \Kdyby\Components\Grinder\Columns\CheckboxColumn
	 */
	public function addCheckColumn($name, $caption = NULL)
	{
		$column = new Columns\CheckboxColumn($this, $name);
		$column->caption = $caption;
		return $this->attachColumn($column);
	}



	/**
	 * @param string $name
	 *
	 * @return \Kdyby\Components\Grinder\Column
	 */
	public function getColumn($name)
	{
		if (!isset($this->columns[$name])) {
			return $this->addColumn($name);
		}

		return $this->columns[$name];
	}



	/**
	 * @param string $type
	 *
	 * @return \Kdyby\Components\Grinder\Column[]|\Iterator
	 */
	public function getColumns($type = NULL)
	{
		if (!$this->columns) {
			foreach ($this->getClass()->getFieldNames() as $fieldName) {
				$this->getColumn($fieldName)->caption = ucFirst($fieldName);
			}
		}

		$columns = new \ArrayIterator($this->columns);
		if ($type !== NULL) {
			return new Nette\Iterators\InstanceFilter($columns, $type);
		}
		return $columns;
	}



	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function hasColumn($name)
	{
		return isset($this->columns[$name]);
	}



	/**
	 * @param \Kdyby\Components\Grinder\Column $column
	 *
	 * @return \Kdyby\Components\Grinder\Column
	 * @throws \Kdyby\InvalidStateException
	 */
	protected function attachColumn(Column $column)
	{
		if (isset($this->columns[$name = $column->getName()])) {
			throw new Kdyby\InvalidStateException("Column with name '$name' already exists.");
		}

		if (!$this->isClassFormColumn($column) && !$this->isColumnNameValid($name)) {
			throw new Kdyby\InvalidStateException("Column name '$name' is not valid.");
		}

		return $this->columns[$name] = $column;
	}



	/**
	 * @param string $class
	 *
	 * @return bool
	 */
	private function isClassFormColumn($class)
	{
		return Nette\Reflection\ClassType::from($class)->is('Kdyby\Components\Grinder\Columns\FormColumn');
	}



	/**
	 * @internal
	 * @param string $name
	 * @param bool $isField
	 *
	 * @return bool
	 */
	public function isColumnNameValid($name, $isField = FALSE)
	{
		$class = $this->getClass();
		$em = $this->getEntityManager();
		foreach (explode('.', $name) as $field) {
			if ($class->hasAssociation($field)) {
				$class = $em->getClassMetadata($class->getAssociationTargetClass($field));

			} elseif ($class->hasField($field)) {
				return TRUE;

			} else {
				return FALSE;
			}
		}

		return $isField ? FALSE : TRUE;
	}



	/**
	 * @param string $name
	 *
	 * @return \Kdyby\Doctrine\Mapping\ClassMetadata
	 */
	private function getColumnMeta($name)
	{
		$class = $this->getClass();
		$em = $this->getEntityManager();
		foreach (explode('.', $name) as $field) {
			if ($class->hasAssociation($field)) {
				$class = $em->getClassMetadata($class->getAssociationTargetClass($field));

			} elseif ($class->hasField($field)) {
				return $class;
			}
		}

		return $class;
	}


	/********************* Data *********************/


	/**
	 * @return \Kdyby\Components\Grinder\GridIterator
	 */
	public function getIterator()
	{
		if ($this->iterator !== NULL) {
			return $this->iterator;
		}

		$qb = clone $this->queryBuilder;
		$qb->setParameters($qb->getParameters());
		return $this->iterator = new GridIterator($this, $qb);
	}



	/**
	 * @return int
	 */
	public function count()
	{
		return $this->getIterator()->getTotalCount();
	}



	/**
	 * @internal
	 * @param int|string $index
	 * @param object $record
	 *
	 * @return object
	 */
	public function bindRecord($index, $record)
	{
		$this->index = (int)$index;
		return $this->record = $record;
	}



	/**
	 * @return int
	 */
	public function getCurrentIndex()
	{
		return $this->index;
	}



	/**
	 * @return object
	 */
	public function getCurrent()
	{
		return $this->record;
	}



	/**
	 * @return int|string
	 */
	public function getCurrentRecordId()
	{
		return $this->getIterator()->getCurrentId();
	}



	/**
	 * @internal
	 * @param string $paramName
	 * @param bool $need
	 *
	 * @return mixed|NULL
	 */
	public function getRecordProperty($paramName, $need = TRUE)
	{
		if ($current = $this->getCurrent()) {
			return Kdyby\Tools\Objects::expand($paramName, $current, $need);
		}

		return NULL;
	}


	/********************* Sorting *********************/


	/**
	 * @param array $sort
	 */
	public function handleSort(array $sort = array())
	{
		if ($this->getPresenter()->isAjax()) {
			$this->invalidateControl();
		}
	}



	/**
	 * @param string $column
	 * @param string $type
	 */
	public function setSorting($column, $type = 'a')
	{
		if (!$this->sort){
			$this->sort = array($column => $type);
		}
	}



	/**
	 * @param string $columnName
	 * @param string $type
	 *
	 * @return bool
	 */
	private function isValidSorting($columnName, $type)
	{
		try {
			return in_array($type, array('asc', 'desc', 'none'))
				&& ($column = $this->getColumn($columnName))
				&& $column->isSortable();

		} catch (Kdyby\InvalidStateException $e) {
			return FALSE;
		}
	}


	/********************* Security *********************/


	/**
	 * CSRF protection
	 * @param \Nette\Http\Session $session
	 */
	public function setupProtection(Nette\Http\Session $session)
	{
		$this->session = $session->getSection(__CLASS__);

		if (!$this->session->securityToken) {
			$this->session->securityToken = Nette\Utils\Strings::random(6);
		}
	}



	/**
	 * CSRF protection
	 * @param string $token
	 *
	 * @return bool
	 */
	public function isTokenValid($token)
	{
		if ($this->session === NULL) {
			return FALSE;
		}

		return $this->session->securityToken === $token;
	}


	/********************* Paging *********************/


	/**
	 * @param int $page
	 */
	public function handlePaginate($page)
	{
		if ($this->getPresenter()->isAjax()) {
			$this->invalidateControl();
		}
	}



	/**
	 * @param int $count
	 */
	public function setItemsPerPage($count)
	{
		$this->paginator->setItemsPerPage($count);
	}



	/**
	 * @return int
	 */
	public function getItemsPerPage()
	{
		return $this->paginator->getItemsPerPage();
	}



	/**
	 * @return \Kdyby\Components\VisualPaginator\Paginator
	 */
	public function getPaginator()
	{
		return $this->paginator;
	}


	/********************* Form *********************/


	/**
	 * @return \Kdyby\Components\Grinder\GridForm
	 */
	public function getForm()
	{
		return $this->getComponent('form');
	}



	/**
	 * @param int $itemId
	 *
	 * @throws \Kdyby\Application\BadRequestException
	 */
	public function handleEditable($itemId = 0)
	{
		/** @var \Nette\Application\Request $request */
		$request = $this->getPresenter()->getRequest();
		if (!$itemId && ($post = $request->getPost()) && isset($post['itemId'])) {
			$itemId = $post['itemId'];
		}

		if (!$itemId) {
			throw new Kdyby\Application\BadRequestException("Missing parameter \$itemId.");
		}

		$payload = array('columns' => array());
		$rows = $this->getForm()->getRows();
		foreach ($rows[$itemId]->getControls() as $control) {
			/** @var \Nette\Forms\Controls\BaseControl $control */
			$payload['columns'][$control->name] = (string)$control->getControl();
		}

		$this->sendPayload($payload);
	}



	/**
	 * @param \Kdyby\Doctrine\Forms\EntityContainer $container
	 *
	 * @throws \Kdyby\InvalidArgumentException
	 */
	public function createColumnControls(EntityContainer $container)
	{
		if ($container->getForm() !== $this->getForm()) {
			throw new Kdyby\InvalidArgumentException("Grinder form does not contain given container.");
		}

		foreach ($this->getColumns() as $column) {
			if (!$column->editable) {
				continue;
			}

			$class = $this->getColumnMeta($column->getName());
			$column->createFormControl($container, $class);
		}

		if (!$this->getForm()->isSubmitted()) {
			$this->getForm()->getMapper()->load();
		}
	}


	/********************* Rendering *********************/


	/**
	 * @param string $class
	 *
	 * @return \Nette\Templating\FileTemplate
	 */
	protected function createTemplate($class = null)
	{
		/** @var \Nette\Templating\FileTemplate|\stdClass $template */
		$template = parent::createTemplate();
		if ($template->getFile() === NULL){
			$template->setFile(__DIR__ . "/Renderers/table.latte");

		} else {
			$template->table = __DIR__ . "/Renderers/table.latte";
		}

		$template->paginator = $this->getPaginator();
		$template->paginatorId = $this->getHtmlId() . '-paginator';
		return $template;
	}



	/**
	 * @return string
	 */
	public function getHtmlId()
	{
		return 'grid-' . $this->lookupPath('Nette\Application\UI\Presenter');
	}



	/**
	 * @internal
	 * @return \Nette\Utils\Html
	 */
	public function getTableControl()
	{
		return Html::el('table', array(
			'id' => $this->getHtmlId(),
			'data-grinder-edit' => $this->link("editable!")
		));
	}



	/**
	 * Renders grid
	 */
	public function render()
	{
		$this->getTemplate()->render();
	}


	/********************* Helpers *********************/


	/**
	 * @param array|object $payload
	 * @throws \Nette\Application\AbortException
	 */
	protected function sendPayload($payload)
	{
		$this->getPresenter()->sendResponse(new JsonResponse($payload));
	}


	/********************* Factories *********************/


	/**
	 * @param \Kdyby\Doctrine\Registry $doctrine
	 * @param string $entityName
	 *
	 * @return \Kdyby\Components\Grinder\Grid
	 */
	public static function createFromEntity(Kdyby\Doctrine\Registry $doctrine, $entityName)
	{
		return new static($doctrine->getDao($entityName)->createQueryBuilder('e'), $doctrine);
	}

}
