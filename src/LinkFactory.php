<?php
namespace Sellastica\LinkFactory;

class LinkFactory
{
	/** @var \Nette\Application\IRouter */
	private $router;
	/** @var \Nette\Http\UrlScript */
	private $refUrl;
	/** @var string */
	private $refUrlHost;


	/**
	 * @param \Nette\Application\IRouter $router
	 * @param \Nette\Http\IRequest $httpRequest
	 * @param \Nette\Http\Request $request
	 */
	public function __construct(
		\Nette\Application\IRouter $router,
		\Nette\Http\IRequest $httpRequest,
		\Nette\Http\Request $request
	)
	{
		$this->router = $router;
		$this->refUrl = $httpRequest->getUrl();
		$this->refUrlHost = $this->refUrl->getHostUrl();
		$this->refUrl = $request->getUrl();
	}

	/**
	 * @param string $destination
	 * @param array $params
	 * @param bool $absolute
	 * @return string
	 */
	public function link($destination, array $params = [], $absolute = false)
	{
		return true === $absolute
			? $this->absoluteLink($destination, $params)
			: $this->createLink($destination, $params);
	}

	/**
	 * @param string $destination
	 * @param array $params
	 * @return mixed
	 */
	public function relativeLink($destination, array $params = [])
	{
		return $this->createLink(\Sellastica\Utils\Strings::removeFromBeginning($destination, '//'), $params);
	}

	/**
	 * @param string $destination
	 * @param array $params
	 * @return mixed
	 */
	public function absoluteLink($destination, array $params = [])
	{
		return $this->createLink('//' . \Sellastica\Utils\Strings::removeFromBeginning($destination, '//'), $params);
	}

	/**
	 * @param string $component
	 * @param string $action
	 * @param array $params
	 * @param bool $absolute
	 * @return string
	 */
	public function componentLink(?string $component, $action, array $params = [], $absolute = false)
	{
		$url = clone $this->refUrl;
		//clear all query parameters
		foreach ($url->getQueryParameters() as $param => $value) {
			$url->setQueryParameter($param, null);
		}

		//query params
		foreach ($params as $param => $value) {
			$url->setQueryParameter(
				$component ? "$component-$param" : $param,
				$value
			);
		}

		//action
		$url->setQueryParameter(
			'do',
			$component ? "$component-$action" : $action
		);
		return true === $absolute
			? $url->getAbsoluteUrl()
			: '/' . $url->getRelativeUrl();
	}

	/**
	 * @param string $destination
	 * @param array $params
	 * @return mixed
	 */
	public function getUrl($destination, array $params = [])
	{
		$link = $this->absoluteLink($destination, $params);
		return new \Sellastica\Http\Url($link);
	}

	/**
	 * Creates link.
	 *
	 * Destination syntax:
	 *  - 'Presenter:action' - creates relative link
	 *  - '//Presenter:action' - creates absolute link
	 *  - 'Presenter:action#fragment' - may contain optional fragment
	 *
	 * @param  string 'Presenter:action' (creates relative link) or '//Presenter:action' (creates absolute link)
	 * @param  array
	 * @return string
	 * @throws InvalidLinkException if router returns NULL
	 */
	private function createLink($destination, array $params = array())
	{
		if (($pos = strrpos($destination, '#')) !== FALSE) {
			$fragment = substr($destination, $pos);
			$destination = substr($destination, 0, $pos);
		} else {
			$fragment = '';
		}

		if (strncmp($destination, '//', 2) === 0) {
			$absoluteUrl = TRUE;
			$destination = substr($destination, 2);
		} else {
			$absoluteUrl = FALSE;
		}

		$pos = strrpos($destination, ':');
		$presenter = substr($destination, 0, $pos);
		if ($pos + 1 < strlen($destination)) {
			$params['action'] = substr($destination, $pos + 1);
		}

		$request = new \Nette\Application\Request($presenter, 'GET', $params);
		$url = $this->router->constructUrl($request, $this->refUrl);
		if ($url === NULL) {
			throw new InvalidLinkException("Router failed to create link to '$destination'.");
		}

		if (!$absoluteUrl && strncmp($url, $this->refUrlHost, strlen($this->refUrlHost)) === 0) {
			$url = substr($url, strlen($this->refUrlHost));
		}

		if ($fragment) {
			$url .= $fragment;
		}

		return $url;
	}
}