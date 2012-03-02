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
class RelatedEntity extends Kdyby\Doctrine\Entities\IdentifiedEntity
{

	/**
	 * @Orm:Column(type="string")
	 */
	public $name;

	/**
	 * @Orm:ManyToOne(targetEntity="RootEntity", inversedBy="children", cascade={"persist"})
	 * @var \Kdyby\Tests\Components\Grinder\Fixtures\RootEntity
	 */
	public $daddy;

	/**
	 * @Orm:ManyToMany(targetEntity="RootEntity", mappedBy="buddies", cascade={"persist"})
	 * @var \Kdyby\Tests\Components\Grinder\Fixtures\RootEntity[]
	 */
	public $buddies;



	/**
	 * @param string $name
	 * @param \Kdyby\Tests\Components\Grinder\Fixtures\RootEntity $daddy
	 */
	public function __construct($name = NULL, RootEntity $daddy = NULL)
	{
		$this->name = $name;
		$this->daddy = $daddy;
		$this->buddies = new ArrayCollection();
	}

}
