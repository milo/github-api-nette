<?php

namespace Milo\Github\NetteExtension\BC;

use Nette, Tracy;


abstract class Extension extends Nette\DI\CompilerExtension
{
	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$this->afterCompileImplementation($class, 'Tracy\Debugger::getBar()->addPanel');
	}

	abstract protected function afterCompileImplementation($classType, $method);
}

interface IBarPanel extends Tracy\IBarPanel
{}
