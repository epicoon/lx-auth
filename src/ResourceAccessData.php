<?php

namespace lx\auth;

use lx\ResourceAccessDataInterface;

class ResourceAccessData implements ResourceAccessDataInterface
{
	private array $rights;

	public function __construct(array $rights)
	{
		$this->rights = $rights;
	}

	public function getData(): array
	{
		return $this->rights;
	}
}
