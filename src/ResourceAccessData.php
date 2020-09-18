<?php

namespace lx\auth;

use lx\ResourceAccessDataInterface;

/**
 * Class ResourceAccessData
 * @package lx\auth
 */
class ResourceAccessData implements ResourceAccessDataInterface
{
	/** @var array */
	private $rights;

	/**
	 * ResourceAccessData constructor.
	 * @param array $rights
	 */
	public function __construct($rights)
	{
		$this->rights = $rights;
	}

	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->rights;
	}
}
