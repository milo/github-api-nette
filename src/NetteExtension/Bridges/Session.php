<?php

namespace Milo\Github\NetteExtension\Bridges;

use Milo\Github;
use Nette;


/**
 * Bridge from Nette\Http\Session to milo/github-api.
 *
 * @author  Miloslav Hůla (https://github.com/milo)
 */
class Session extends Nette\Object implements Github\Storages\ISessionStorage
{
	/** @var Nette\Http\SessionSection */
	private $session;


	public function __construct(Nette\Http\Session $session)
	{
		$this->session = $session->getSection(Github\Storages\SessionStorage::SESSION_KEY);
	}


	/**
	 * @param  string
	 * @return mixed
	 */
	public function get($name)
	{
		return $this->session->offsetGet($name);
	}


	/**
	 * @param  string
	 * @param  mixed
	 * @return self
	 */
	public function set($name, $value)
	{
		$this->session->offsetSet($name, $value);
		return $this;
	}


	/**
	 * @param  string
	 * @return self
	 */
	public function remove($name)
	{
		$this->session->offsetUnset($name);
		return $this;
	}

}
