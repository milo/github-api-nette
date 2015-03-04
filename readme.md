# Introduction
This is the [Nette Framework](http://nette.org) extension for [milo/github-api](https://github.com/milo/github-api) library use. It brings you:

- prepared presenter for sign-in and sign-out actions
- Tracy panel
- `Milo\Github\Api` service in container
- and few more things...


# Installation
The extension is compatible with Nette 2.0.x, 2.1.x and 2.2.x. I'm keeping it compatible with development branch. If you hit the problem, please, open an [issue](https://github.com/milo/github-api-nette/issues).

For installation use [Composer](https://getcomposer.org/):
```
# With Nette >=2.2
composer require milo/github-api-nette @dev

# With Nette <2.2
composer require milo/github-api-nette 2.1.*@dev 
```

Register the extension in `config.neon`:
```
extensions:
	github: Milo\Github\NetteExtension\Extension
```

But with Nette 2.0.x register the extension manually in bootstrap:
```php
Milo\Github\NetteExtension\Extension::register($configurator);
```

Since you register the extension you get a new service in DI container:
- `github.api -> Milo\Github\Api`

And when you set `clientId` and `clientSecret` (mentioned later) in config file, you get next two:
- `github.login -> Milo\Github\OAuth\Login`
- `github.user -> Milo\Github\NetteExtension\User`


# Configuration
Extension works without any configuration. But your Github API requests will be rate limited and non-authenticated.

Configure the extension in `config.neon`. If you have one static token, you can set it manualy:
```
github:
	auth:
		token: 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
```

If you want to obtain the token dynamically by authentication procedure, set the `clientId` and `clientSecret`:
```
github:
	auth:
		clientId: 'xxxxxxxxxxxx'
		clientSecret: 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
```

You can use both simultaneously. The token obtained by authentication will be used when obtained.


Whole configuration:
```
services:
	ownClient: Milo\Github\Http\StreamClient

github:
	cached: FALSE       # (default TRUE) read below...
	client: @ownClient  # (default NULL) set own HTTP client
	auth:
		token: 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
		clientId: 'xxxxxxxxxxxx'
		clientSecret: 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
		scopes: ['user', 'repo']  # (default [])
```


# Caching
The `cached` configuration option controls caching mechanism.
- `FALSE` - no caching will be used

With these, Http\CachedClient will be used:
- `TRUE` - updates will be checked every request
- `(int)` - updates will not be checked for int seconds
- `INF` - updates will never be checked (permanent cache)

**WARNING** - Values `(int)` and `INF` are for development purpose only and should never be used on production. It's because the cache may be outdated and Github may hold newer data.


# Authentication
To help with OAuth token obtaining, there is a prepared abstract `Milo\Github\NetteExtension\Presenter` for you. Use it in your application as:
```php
namespace App\Presenters;

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
- `Github:signIn` for logging in
- `Github:signOut` for logging out

**NOTE**: The prepared presenter requires clientId and clientSecret to be configured.

Even you are or you are not authenticated, you have a `github.user` [Milo\Github\NetteExtension\User](https://github.com/milo/github-api-nette/blob/master/src/NetteExtension/User.php) service in DI container. You can check `$user->isLoggedIn()` and get some basic user info like, login, name, avatar URL...


# Tracy panel
![Tracy panel screenshot](https://github.com/milo/github-api-nette/raw/master/screenshot.png)


# License
The MIT License (MIT)

Copyright (c) 2014 Miloslav Hůla

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
