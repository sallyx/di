<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\DI\Extensions;

use Nette;


/**
 * DI extension.
 */
class DIExtension extends Nette\DI\CompilerExtension
{
	public $defaults = [
		'debugger' => FALSE,
		'accessors' => FALSE,
	];

	/** @var bool */
	private $debugMode;

	/** @var int */
	private $time;


	public function __construct($debugMode = FALSE)
	{
		$this->debugMode = $debugMode;
		$this->time = microtime(TRUE);
	}


	public function loadConfiguration()
	{
		$config = $this->validateConfig($this->defaults);
		if ($config['accessors']) {
			$this->getContainerBuilder()->parameters['container']['accessors'] = TRUE;
		}
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$initialize = $class->getMethod('initialize');
		$container = $this->getContainerBuilder();

		if ($this->debugMode && $this->config['debugger']) {
			Nette\Bridges\DITracy\ContainerPanel::$compilationTime = $this->time;
			$initialize->addBody($container->formatPhp('?;', [
				new Nette\DI\Statement('@Tracy\Bar::addPanel', [new Nette\DI\Statement(Nette\Bridges\DITracy\ContainerPanel::class)]),
			]));
		}

		foreach (array_filter($container->findByTag('run')) as $name => $on) {
			$initialize->addBody('$this->getService(?);', [$name]);
		}

		if (!empty($this->config['accessors'])) {
			$definitions = $container->getDefinitions();
			ksort($definitions);
			foreach ($definitions as $name => $def) {
				if (Nette\PhpGenerator\Helpers::isIdentifier($name)) {
					$type = $def->getImplement() ?: $def->getClass();
					$class->addDocument("@property $type \$$name");
				}
			}
		}
	}

}
