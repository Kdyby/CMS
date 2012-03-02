<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Tests\Components\Grinder\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Kdyby;
use Nette;



/**
 * @Orm:Entity()
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
class RootEntity extends Kdyby\Doctrine\Entities\IdentifiedEntity
{

	/**
	 * @Orm:Column(type="string")
	 */
	public $name;

	/**
	 * @Orm:ManyToOne(targetEntity="RelatedEntity", cascade={"persist"})
	 * @var \Kdyby\Tests\Components\Grinder\Fixtures\RelatedEntity
	 */
	public $daddy;

	/**
	 * @Orm:OneToMany(targetEntity="RelatedEntity", mappedBy="daddy", cascade={"persist"})
	 * @var \Kdyby\Tests\Components\Grinder\Fixtures\RelatedEntity[]
	 */
	public $children;

	/**
	 * @Orm:ManyToMany(targetEntity="RelatedEntity", inversedBy="buddies", cascade={"persist"})
	 * @var \Kdyby\Tests\Components\Grinder\Fixtures\RelatedEntity[]
	 */
	public $buddies;



	/**
	 * @param string $name
	 */
	public function __construct($name = NULL)
	{
		$this->name = $name;
		$this->children = new ArrayCollection();
		$this->buddies = new ArrayCollection();
	}

}
