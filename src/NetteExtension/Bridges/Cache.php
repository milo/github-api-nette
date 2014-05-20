<?php

namespace Milo\Github\NetteExtension\Bridges;

use Milo\Github;
use Nette;


/**
 * Bridge from Nette\Caching to milo/github-api.
 *
 * @author  Miloslav Hůla (https://github.com/milo)
 */
class Cache extends Nette\Object implements Github\Storages\ICache
{
	/** @var Nette\Caching\Cache */
	private $cache;

	public function __construct(Nette\Caching\IStorage $storage)
	{
		$this->cache = new Nette\Caching\Cache($storage, 'milo.github-api');
	}


	/**
	 * @param  string
	 * @param  mixed
	 * @return mixed
	 */
	public function save($key, $value)
	{
		$this->cache->save($key, $value);
		return $value;
	}


	/**
	 * @param  string
	 * @return mixed|NULL
	 */
	public function load($key)
	{
		return $this->cache->load($key);
	}

}
