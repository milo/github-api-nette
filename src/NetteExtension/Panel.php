<?php

namespace Milo\Github\NetteExtension;

use Milo\Github;
use Nette, Tracy;


/**
 * Nette Tracy panel (http://tracy.nette.org) for milo/github-api library.
 *
 * @author  Miloslav Hůla (https://github.com/milo)
 */
class Panel extends Nette\Object implements BC\IBarPanel
{
	/** @var Nette\Http\Session */
	private $session;

	/** @var Github\Api */
	private $api;

	/** @var User|NULL */
	private $user;

	/** @var Github\Http\Message[] */
	private $messages = [];

	/** @var \stdClass */
	private $rateLimit;


	public function __construct(Messages $messages, Nette\Http\Session $session, Github\Api $api, User $user = NULL)
	{
		$this->session = $session->getSection('milo.github.nette-extension');
		$this->api = $api;
		$this->user = $user;

		foreach ($messages->getAll() as $message) {
			$this->onMessage($message);
		}

		# Change handler
		$api->getClient()->onRequest([$this, 'onMessage']);
		$api->getClient()->onResponse([$this, 'onMessage']);
	}


	/**
	 * @return string
	 */
	public function getTab()
	{
		$tab = '<img width="16" height="16" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAACXBIWXMAAC4jAAAuIwF4pT92AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAqtJREFUeNpNU0tPE2EU/ZgWgbbQ9wtIS8WIBhNaCiaitEZBJZomYmJhxlBbXWDsyzRl0UBLn2lLiol/wD/gzp2auJDgyrVx40JdmRiNz1bLjPc0LeniZm7uPec+zv2GbW9vs0wmw9LptLxWq7GN5AabmXY/sJotb00GYwMGHzHkgAEWHHBbZDKuWCgw/03/zNExxzMi/TAbTQfdhhhywAALDrhsa2tLXiwW2eLCwo1BpapBJhFYspjMIEow+IghBwyw4BBX1hqJqrpVCmV9xDr8xe2afkSED5ohtWTUG37C4COGHDDArvj909VqlcPOMod97JVWrZHou5fL5VgkHNYJPH8mHA6PwuAjhhwwwNpttv3WCiROgsar67U6kZKvNzc35QXasVQqMXy7feSAMej0IuGlpStLi4wUfk/j/VMPDiEgAAwgKS0j62mbDDHkCHMLWNJGHB0eec7apxJ1Gq14b319Ep1A6pyp68w9yAEDLApgckZOg6xJQh3wPD9H6ra6tc97aIghx6+uniWs2D7tbxR4Rys0oLRryvmwUqkwiNXp3jHEkHNOOXchIiagizxlnnPzd6hS02qxfiJhfp33eO8nk8k+ujFHY2N0dOcSiYQKORKwQQ2bEHH5+vIcS6VSbMxm36NqbzzznpWBI/2Som+gvnDh4rV8Pt/qfHnx0lWjzvCx/R7+guyw218gx3Z2drhQMHhM2a8QT8/O7gYCgUlSOhqLxdTZbJbhxQm8cArKU5M/eJG0wrfg7eDxcrksw3gynIcei3dINfjV5XQ99vl8d6ORqKarwDhERmcif+ZXeS84racMgWhPDoFQMOSgH+ZlLyeXBEGYABmnC6ytjdME309OnHgCDLDgHP6NcFANKtNYLB6Payku71yAwL2RSMQCIjDAdt7Hfwo5VHRB0FFmAAAAAElFTkSuQmCC"> ';
		if ($this->user && $this->user->isLoggedIn()) {
			$tab .= "<img height='16' src='" . htmlSpecialChars($this->user->avatarUrl) . "'>";
		} else {
			$tab .= 'N/A';
		}
		return $tab;
	}


	/**
	 * @return string
	 */
	public function getPanel()
	{
		$user = $this->user;
		$token = $this->api->getToken();
		$client = $this->api->getClient();

		if ($this->rateLimit) {
			$freshLimit = TRUE;
			$rateLimit = $this->rateLimit;
		} elseif ($this->session->rateLimit) {
			$freshLimit = FALSE;
			$rateLimit = $this->session->rateLimit;
		} else {
			$freshLimit = FALSE;
			$rateLimit = NULL;
		}

		$messages = $this->messages;

		ob_start();
		require __DIR__ . '/Panel.phtml';
		return ob_get_clean();
	}


	public function onMessage(Github\Http\Message $message)
	{
		if ($message instanceof Github\Http\Response) {
			$this->saveRateLimit($message);
		}

		$this->messages[] = $message;
	}


	private function saveRateLimit(Github\Http\Response $response)
	{
		if (($previous = $response->getPrevious()) && $previous->isCode(Github\Http\Response::S304_NOT_MODIFIED)) {
			# Cached
			$response = $previous;
		}

		$this->session->rateLimit = $this->rateLimit = (object) [
			'limit' => $response->getHeader('X-RateLimit-Limit', '???'),
			'remaining' => $response->getHeader('X-RateLimit-Remaining', '???'),
			'reset' => $response->getHeader('X-RateLimit-Reset'),
		];
	}


	/**
	 * @param  string
	 * @return string
	 */
	private function escape($var)
	{
		return htmlSpecialChars($var);
	}


	/**
	 * @param  mixed
	 * @return string
	 */
	private function dumpHtml($var)
	{
		if (class_exists('Tracy\Dumper')) {
			return Tracy\Dumper::toHtml($var, [Tracy\Dumper::COLLAPSE => TRUE]);
		} elseif (class_exists('Nette\Diagnostics\Dumper')) {
			return Nette\Diagnostics\Dumper::toHtml($var, [Nette\Diagnostics\Dumper::COLLAPSE => TRUE]);
		} else {
			return Nette\Diagnostics\Debugger::dump($var, TRUE);
		}
	}


	/**
	 * @param  mixed
	 * @return \DateInterval
	 */
	private function createInterval($value)
	{
		$dt = class_exists('Nette\Utils\DateTime')
			? Nette\Utils\DateTime::from($value)
			: Nette\DateTime::from($value);

		return (new \DateTime)->diff($dt);
	}

}
