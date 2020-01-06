<?php

namespace lx\auth\plugin\getToken\backend;

class Respondent extends \lx\Respondent
{
	public function tryAuthenticate()
	{
		$gate = $this->app->authenticationGate;
		
		$result = $gate->authenticateUser();
		if ($result) {
			return ['success' => true];
		}

		if ($gate->tokenIsExpired()) {
			return [
				'success' => false,
				'message' => 'expired',
			];
		}

		return [
			'success' => false,
			'message' => 'unknown',
		];
	}
	
	public function refreshTokens($refreshToken)
	{
		$gate = $this->app->authenticationGate;

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
