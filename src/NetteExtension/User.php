<?php

namespace Milo\Github\NetteExtension;

use Milo\Github;
use Nette;


/**
 * Github user info.
 *
 * @author  Miloslav Hůla (https://github.com/milo)
 *
 * @property-read  string|NULL  $id
 * @property-read  string|NULL  $login
 * @property-read  string|NULL  $name
 * @property-read  string|NULL  $avatarUrl
 * @property-read  string|NULL  $email
 */
class User extends Nette\Object
{
	/** @var array */
	private $defaults = [
		'id' => NULL,
		'login' => NULL,
		'name' => NULL,
		'avatarUrl' => NULL,
		'email' => NULL,
	];

	/** @var array */
	private $info;

	/** @var bool */
	private $isLoggedIn = FALSE;


	public function __construct(Github\OAuth\Login $login, Github\Api $api, Nette\Http\Session $session)
	{
		$session = $session->getSection('milo.github.nette-extension.user');

		if ($login->hasToken()) {
			$token = $login->getToken();

			$hash = sha1($token->getValue());
			if ($session->hash !== $hash) {
				$info = $this->defaults;

				$user = $api->decode($api->get('/user'));
				$info['id'] = $user->id;
				$info['login'] = $user->login;
				$info['name'] = $user->name;
				$info['avatarUrl'] = $user->avatar_url;

				if ($token->hasScope('user:email')) {
					$emails = $api->decode($api->get('/user/emails'));
					foreach ($emails as $email) {
						$info['email'] = $email->email;
						if ($email->primary) {
							break;
						}
					}
				}

				$session->info = $info;
				$session->hash = $hash;
			}
			$this->isLoggedIn = TRUE;

		} else {
			$session->remove();
			$session->info = $this->defaults;
		}

		$this->info = $session->info;
	}


	/**
	 * @return bool
	 */
	public function isLoggedIn()
	{
		return $this->isLoggedIn;
	}


	/**
	 * @param  string
	 * @return string|NULL
	 */
	public function & __get($name)
	{
		if (array_key_exists($name, $this->info)) {
			return $this->info[$name];
		}

		return parent::__get($name);
	}

}
