<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

@header('X-Generated-By: Kdyby CMS'); // @ - headers may be sent

define('KDYBY_CMS_DIR', __DIR__);

// Register CMS
$configurator->registerCMS();

// Load config
$container = $configurator->loadConfig(APP_DIR . '/config.neon');

// Run the Application!
$container->application->run();