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
	/** @var int|NULL */
	private $expire;

	/** @var Nette\Caching\Cache */
	private $cache;


	/**
	 * @param  int|NULL  seconds to expire; NULL means never
	 */
	public function __construct($timeout, Nette\Caching\IStorage $storage)
	{
		$this->expire = $timeout;
		$this->cache = new Nette\Caching\Cache($storage, 'milo.github-api');
	}


	/**
	 * @param  string
	 * @param  mixed
	 * @return mixed
	 */
	public function save($key, $value)
	{
		$dp = $this->expire === NULL
			? NULL
			: [Nette\Caching\Cache::EXPIRE => $this->expire];

		$this->cache->save($key, [$value, $this->expire], $dp);
		return $value;
	}


	/**
	 * @param  string
	 * @return mixed|NULL
	 */
	public function load($key)
	{
		$cached = $this->cache->load($key);
		if (!is_array($cached) || $cached[1] !== $this->expire) {
			return NULL;
		}

		return $cached[0];
	}

}
