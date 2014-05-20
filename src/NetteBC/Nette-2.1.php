<?php

namespace Milo\Github\NetteExtension\BC;

use Nette;


abstract class Extension extends Nette\DI\CompilerExtension
{
	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$this->afterCompileImplementation($class, 'Nette\Diagnostics\Debugger::getBar()->addPanel');
	}

	abstract protected function afterCompileImplementation($classType, $method);
}

interface IBarPanel extends Nette\Diagnostics\IBarPanel
{}
