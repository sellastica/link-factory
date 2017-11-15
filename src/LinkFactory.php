<?php
namespace Sellastica\LinkFactory;

use Nette;
use Sellastica\Http\Url;
use Sellastica\Utils\Strings;

class LinkFactory
{
	/** @var \Nextras\Application\LinkFactory */
	private $linkFactory;
	/** @var Nette\Http\UrlScript */
	private $refUrl;


	/**
	 * @param \Nextras\Application\LinkFactory $linkFactory
	 * @param Nette\Http\Request $request
	 */
	public function __construct(
		\Nextras\Application\LinkFactory $linkFactory,
		Nette\Http\Request $request
	)
	{
		$this->linkFactory = $linkFactory;
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
			: $this->linkFactory->link($destination, $params);
	}

	/**
	 * @param string $destination
	 * @param array $params
	 * @return mixed
	 */
	public function relativeLink($destination, array $params = [])
	{
		return $this->linkFactory->link(Strings::removeFromBeginning($destination, '//'), $params);
	}

	/**
	 * @param string $destination
	 * @param array $params
	 * @return mixed
	 */
	public function absoluteLink($destination, array $params = [])
	{
		return $this->linkFactory->link('//' . Strings::removeFromBeginning($destination, '//'), $params);
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
		return new Url($link);
	}
}