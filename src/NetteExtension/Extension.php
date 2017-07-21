<?php

namespace Milo\Github\NetteExtension;

use Milo\Github;
use Nette\DI;
use Nette\Utils\Validators;
use Tracy;


/**
 * Integration of milo/github-api into Nette Framework (http://nette.org)
 *
 * @author  Miloslav Hůla (https://github.com/milo)
 */
final class Extension extends DI\CompilerExtension
{
	/** @var array */
	private $defaults = [
		'client' => NULL,
		'cached' => TRUE,
		'auth' => [
			'token' => NULL,
			'clientId' => NULL,
			'clientSecret' => NULL,
			'scopes' => [],
			'asUrlParameters' => TRUE,
		],
	];

	/** @var bool */
	private $debugMode;


	/**
	 * @param  bool
	 */
	public function __construct($debugMode)
	{
		$this->debugMode = (bool) $debugMode;
	}


	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		# HTTP client
		if ($config['client'] !== NULL && !preg_match('/^@.+$/i', $config['client'])) {
			throw new \LogicException("Configuration option 'client' must be a service name, '$config[client]' given.");
		}

		$cached = $this->sanitizeCached($config['cached']);
		if ($cached) {
			$builder
				->addDefinition($this->prefix('client.cache'))
				->setClass(Github\NetteExtension\Bridges\Cache::class, [
					is_int($cached) ? $cached : NULL,
				])
				->setAutowired(FALSE);

			$clientService = '@' . $this->prefix('client');
			$builder
				->addDefinition($this->prefix('client'))
				->setClass(Github\Http\CachedClient::class, [
					'@' . $this->prefix('client.cache'),
					$config['client'],
					$cached !== TRUE,
				])
				->setAutowired(FALSE);

		} elseif ($config['client'] === NULL) {
			$clientService = NULL;

		} else {
			$clientService = $config['client'];
		}

		# API
		$apiService = $builder
			->addDefinition($this->prefix('api'))
			->setClass(Github\Api::class, [$clientService]);

		if (isset($config['auth']['token'])) {
			$apiService->addSetup('?->setToken(new Milo\Github\OAuth\Token(?))', [
				'@self',
				(string) $config['auth']['token']
			]);
		}

		# Login
		$userService = NULL;
		Validators::assert($config['auth'], 'array');
		if (isset($config['auth']['clientId'], $config['auth']['clientSecret'])) {
			# Auth.Config
			$builder
				->addDefinition($this->prefix('auth.config'))
				->setClass(Github\OAuth\Configuration::class, [
					$config['auth']['clientId'],
					$config['auth']['clientSecret'],
					$config['auth']['scopes'],
				])
				->setAutowired(FALSE);

			# Auth.Session
			$builder
				->addDefinition($this->prefix('auth.session'))
				->setClass(Github\NetteExtension\Bridges\Session::class)
				->setAutowired(FALSE);

			# Login
			$builder
				->addDefinition($this->prefix('login'))
				->setClass(Github\OAuth\Login::class, [
					'@' . $this->prefix('auth.config'),
					'@' . $this->prefix('auth.session'),
					$clientService,
				]);

			# Api::setToken()
			$apiService->addSetup('if (?->hasToken()) { ?->setToken(?->getToken()); }', [
				'@' . $this->prefix('login'),
				'@self',
				'@' . $this->prefix('login'),
			]);

			# Api::setDefaultParameters()
			if ($config['auth']['asUrlParameters'] && empty($config['auth']['token'])) {
				$apiService->addSetup('if (!?->hasToken()) { ?->setDefaultParameters(?); }', [
					'@' . $this->prefix('login'),
					'@self',
					[
						'client_id' => $config['auth']['clientId'],
						'client_secret' => $config['auth']['clientSecret'],
					]
				]);
			}

			# User
			$userService = '@' . $this->prefix('user');
			$builder
				->addDefinition($this->prefix('user'))
				->setClass(Github\NetteExtension\User::class, [
					'@' . $this->prefix('login'),
					'@' . $this->prefix('api'),
				]);
		}

		# Panel
		if ($this->debugMode && class_exists(Tracy\Debugger::class)) {
			$builder
				->addDefinition($this->prefix('messages'))
				->setClass(Github\NetteExtension\Messages::class)
				->setAutowired(FALSE);

			$apiService
				->addSetup('?->getClient()->onRequest([?, "onMessage"])', ['@self', '@' . $this->prefix('messages')])
				->addSetup('?->getClient()->onResponse([?, "onMessage"])', ['@self', '@' . $this->prefix('messages')]);

			$builder
				->addDefinition($this->prefix('panel'))
				->setClass(Github\NetteExtension\Panel::class, [
					'@' . $this->prefix('messages'),
					'@session',
					'@' . $this->prefix('api'),
					$userService
				])
				->setAutowired(FALSE);

			$builder
				->getDefinition('tracy.bar')
				->addSetup('addPanel', ['@' . $this->prefix('panel')]);
		}
	}


	/**
	 * @return mixed
	 */
	private function sanitizeCached($value)
	{
		if (is_bool($value)) {
			return $value;

		} elseif ((is_string($value) && strtoupper($value) === 'INF') || $value === INF) {
			return INF;

		} elseif (is_int($value) && $value > 0) {
			return $value;
		}

		throw new \LogicException("Configuration option 'cached' must be bool, positive integer or INF, but '$value' given.");
	}

}
