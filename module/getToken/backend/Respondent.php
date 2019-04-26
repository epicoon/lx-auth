<?php

namespace lx\auth\module\getToken\backend;

class Respondent extends \lx\Respondent {
	public function refreshTokens($refreshToken) {
		$gate = \lx::$components->authenticationGate;

		$pare = $gate->refreshTokens($refreshToken);
		if ($pare === false) {
			return ['success' => false, 'error' => 403];
		}

		return [
			'success' => true,
			'token' => 'Bearer ' . $pare[0]->token,
			'refreshToken' => 'Bearer ' . $pare[1]->token,
		];
	}
}
