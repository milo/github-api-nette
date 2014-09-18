<?php

namespace Milo\Github\NetteExtension;

use Nette;
use Nette\Utils\Validators;


/**
 * Integration of milo/github-api into Nette Framework (http://nette.org)
 *
 * @author  Miloslav Hůla (https://github.com/milo)
 */
final class Extension extends BC\Extension
{
	/** @var array */
	public $defaults = [
		'debugger' => '%debugMode%',
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


	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		# HTTP client
		if ($config['client'] !== NULL && !preg_match('/^@.+$/i', $config['client'])) {
			throw new \LogicException("Option client must be a service name, '$config[client]' given.");
		}

		if ($config['cached']) {
			$builder->addDefinition($this->prefix('client.cache'))
				->setClass('Milo\Github\NetteExtension\Bridges\Cache')
				->setAutowired(FALSE);

			$clientService = "@{$this->prefix('client')}";
			$builder->addDefinition($this->prefix('client'))
				->setClass('Milo\Github\Http\CachedClient', [
					"@{$this->prefix('client.cache')}",
					$config['client'],
				])
				->setAutowired(FALSE);

		} elseif ($config['client'] === NULL) {
			$clientService = NULL;

		} else {
			$clientService = $config['client'];
		}

		# API
		$apiService = $builder->addDefinition($this->prefix('api'))
			->setClass('Milo\Github\Api', [$clientService]);

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
			$builder->addDefinition($this->prefix('auth.config'))
				->setClass('Milo\Github\OAuth\Configuration', [
					$config['auth']['clientId'],
					$config['auth']['clientSecret'],
					$config['auth']['scopes'],
				])
				->setAutowired(FALSE);

			# Auth.Session
			$builder->addDefinition($this->prefix('auth.session'))
				->setClass('Milo\Github\NetteExtension\Bridges\Session')
				->setAutowired(FALSE);

			# Login
			$builder->addDefinition($this->prefix('login'))
				->setClass('Milo\Github\OAuth\Login', [
					"@{$this->prefix('auth.config')}",
					"@{$this->prefix('auth.session')}",
					$clientService,
				]);

			# Api::setToken()
			$apiService->addSetup('if (?->hasToken()) { ?->setToken(?->getToken()); }', [
				"@{$this->prefix('login')}",
				'@self',
				"@{$this->prefix('login')}",
			]);

			# Api::setDefaultParameters()
			if ($config['auth']['asUrlParameters'] && empty($config['auth']['token'])) {
				$apiService->addSetup('if (!?->hasToken()) { ?->setDefaultParameters(?); }', [
					"@{$this->prefix('login')}",
					'@self',
					[
						'client_id' => $config['auth']['clientId'],
						'client_secret' => $config['auth']['clientSecret'],
					]
				]);
			}

			# User
			$userService = "@{$this->prefix('user')}";
			$builder->addDefinition($this->prefix('user'))
				->setClass('Milo\Github\NetteExtension\User', [
					"@{$this->prefix('login')}",
					"@{$this->prefix('api')}",
				]);
		}

		# Panel
		if ($config['debugger']) {
			$builder->addDefinition($this->prefix('messages'))
				->setClass('Milo\Github\NetteExtension\Messages')
				->setAutowired(FALSE);

			$apiService
				->addSetup('?->getClient()->onRequest([?, "onMessage"])', ['@self', "@{$this->prefix('messages')}"])
				->addSetup('?->getClient()->onResponse([?, "onMessage"])', ['@self', "@{$this->prefix('messages')}"]);

			$builder->addDefinition($this->prefix('panel'))
				->setClass('Milo\Github\NetteExtension\Panel', [
					"@{$this->prefix('messages')}",
					'@session',
					"@{$this->prefix('api')}",
					$userService
				])
				->setAutowired(FALSE);
		}
	}


	protected function afterCompileImplementation($classType, $method)
	{
		$config = $this->getConfig($this->defaults);
		if ($config['debugger']) {
			$classType->methods['initialize']->addBody($method . '($this->getService(?));', [$this->prefix('panel')]);
		}
	}


	public static function register($configurator, $name = 'github')
	{
		$configurator->onCompile[] = function ($config, $compiler) use ($name) {
			$compiler->addExtension($name, new static);
		};
	}

}
