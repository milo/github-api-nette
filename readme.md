# Introduction
This is the [Nette Framework](http://nette.org) extension for [milo/github-api](https://github.com/milo/github-api) library use. It brings you:

- presenter prepared for sign-in and sign-out actions
- Tracy panel
- `Milo\Github\Api` service in container
- and few more things...


# Installation
The extension is compatible with Nette 2.0.x, 2.1.x and 2.2.x. I'm keeping it compatible with development branch. If you hit the problem, please, open an [issue](https://github.com/milo/github-api-nette/issues).

For installation use [Composer](https://getcomposer.org/):
```
composer require milo/github-api-nette @dev
```

With Nette 2.0.x register the extension in bootstrap:
```php
Milo\Github\NetteExtension\Extension::register($configurator);
```

With a newer Nette version register the extension in `config.neon`:
```
extensions:
	github: Milo\Github\NetteExtension\Extension
```

Since you register the extension you get a new service in DI container:
- `github.api -> Milo\Github\Api`

And when you set `clientId` and `clientSecret` (mentioned later) in config file, you get next two:
- `github.login -> Milo\Github\OAuth\Login`
- `github.user -> Milo\Github\NetteExtension\User`


# Configuration
Extension works without any configuration. But your Github API requests will be rate limited and non-authenticated.

Configure the extension in `config.neon`, add secrets to enable the authentication. Now, you are still non-authenticated but your rate limit is higher:
```
github:
	auth:
		clientId: 'xxxxxxxxxxxx'
		clientSecret: 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
```

Advanced configuration:
```
services:
	ownClient: Milo\Github\Http\StreamClient

github:
	cached: FALSE       # (default TRUE) disable HTTP caching (Http\CachedClient will not be used)
	client: @ownClient  # (default NULL) set own HTTP client
	auth:
		clientId: 'xxxxxxxxxxxx'
		clientSecret: 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
		scopes: ['user', 'repo']  # (default [])
```


# Authentication
To help with OAuth token obtaining, there is a prepared abstract `Milo\Github\NetteExtension\Presenter` for you. Use it in your application as:
```php
namespace App\Module\Presenters;

class GithubPresenter extends Milo\Github\NetteExtension\Presenter
{
	# Called after successfull authentication on Github web page
	public function actionSignInRedirect()
	{
		$this->flashMessage('Signed IN');
		$this->redirect('Homepage:');
	}


	# Called on logging out
	public function actionSignOutRedirect()
	{
		$this->flashMessage('Signed OUT');
		$this->redirect('Homepage:');
	}
}
```

Create links or redirect to following presenter actions in a usual way:
- `Module:Presenter:signIn` for logging in
- `Module:Presenter:signOut` for logging out

Even you are or you are not authenticated, you have a `github.user` [Milo\Github\NetteExtension\User](https://github.com/milo/github-api-nette/blob/master/src/NetteExtension/User.php) service in DI container. You can check `$user->isLoggedIn()` and get some basic user info like, login, name, avatar URL...


# Tracy panel
![Tracy panel screenshot](https://github.com/milo/github-api-nette/raw/master/screenshot.png)


# License
The MIT License (MIT)

Copyright (c) 2014 Miloslav Hůla

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL SIMON TATHAM BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
