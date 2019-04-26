<?php

namespace lx\auth;

use lx\Button;

class LogoutButton extends Button {
	public function __construct($config = []) {
		parent::__construct($config);

		if (isset($config['url'])) {
			$this->url = $config['url'];
		}
	}
}
