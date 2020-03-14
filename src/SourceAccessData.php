<?php

namespace lx\auth;

use lx\SourceAccessDataInterface;

/**
 * Class SourceAccessData
 * @package lx\auth
 */
class SourceAccessData implements SourceAccessDataInterface
{
	/** @var array */
	private $rights;

	/**
	 * SourceAccessData constructor.
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
