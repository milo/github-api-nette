<?php

namespace Milo\Github\NetteExtension\BC;

use Nette;


abstract class Extension extends Nette\Config\CompilerExtension
{
	public function afterCompile(Nette\Utils\PhpGenerator\ClassType $class)
	{
		$this->afterCompileImplementation($class, 'Nette\Diagnostics\Debugger::addPanel');
	}

	abstract protected function afterCompileImplementation($classType, $method);
}

interface IBarPanel extends Nette\Diagnostics\IBarPanel
{}
