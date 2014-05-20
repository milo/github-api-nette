<?php

namespace Milo\Github\NetteExtension;

use Milo\Github;
use Nette;


/**
 * HTTP requests/responses storage. The only purpose is to catch all HTTP API traffic (e.g. in services constructor).
 *
 * @author  Miloslav Hůla  (https://github.com/milo)
 * @internal
 */
class Messages extends Nette\Object
{
	/** @var Github\Http\Message[] */
	private $messages = [];


	public function onMessage(Github\Http\Message $message)
	{
		$this->messages[] = $message;
	}


	/**
	 * @param  bool
	 * @return Github\Http\Message[]
	 */
	public function getAll($clean = TRUE)
	{
		$messages = $this->messages;
		if ($clean) {
			$this->messages = [];
		}

		return $messages;
	}

}
