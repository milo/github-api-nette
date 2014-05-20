<?php

namespace Milo\Github\NetteExtension;

use Milo\Github;
use Nette;


/**
 * @author  Miloslav Hůla (https://github.com/milo)
 */
abstract class Presenter extends Nette\Application\UI\Presenter
{
	/** @var Github\OAuth\Login */
	protected $login;


	public function __construct(Github\OAuth\Login $login)
	{
		$this->login = $login;
	}


	/**
	 * Signs in the user (obtain a token).
	 */
	public function actionSignIn()
	{
		if ($this->login->hasToken()) {
			$this->forward('signInRedirect');
		}

		$this->login->askPermissions($this->link('//signInBacklink'), function($url) {
			$this->redirectUrl($url);
		});
	}


	/**
	 * Github back redirection action.
	 * @param  string
	 * @param  string
	 */
	public function actionSignInBacklink($code, $state)
	{
		$this->login->obtainToken($code, $state);
		$this->forward('signInRedirect');
	}


	/**
	 * Signs out the user (drops the token).
	 */
	public function actionSignOut()
	{
		$this->login->dropToken();
		$this->forward('signOutRedirect');
	}


	/**
	 * Forwarded here after signing in. Should redirect.
	 */
	abstract public function actionSignInRedirect();


	/**
	 * Forwarded here after signing out. Should redirect.
	 */
	abstract public function actionSignOutRedirect();

}
