<?php

namespace lx\auth\plugin\getToken\backend;

use lx\auth\OAuth2AuthenticationGate;
use lx\AuthenticationInterface;
use lx\ResponseCodeEnum;

class Respondent extends \lx\Respondent
{
	public function tryAuthenticate()
	{
	    /** @var AuthenticationInterface $gate */
		$gate = $this->app->authenticationGate;
		if ($gate->authenticateUser()) {

		    return $this->prepareResponse(
		        $this->app->userProcessor->getPublicData()
            );
		}

		if ($gate->tokenIsExpired()) {

		    return $this->prepareErrorResponse('expired', ResponseCodeEnum::UNAUTHORIZED);
		}
		
		if ($gate->getProblemCode() == OAuth2AuthenticationGate::AUTH_PROBLEM_TOKEN_NOT_FOUND) {

            return $this->prepareErrorResponse('token not found', ResponseCodeEnum::UNAUTHORIZED);
        }

		return $this->prepareErrorResponse('Internal server error');
	}
	
	public function refreshTokens($refreshToken)
	{
		$gate = $this->app->authenticationGate;

		$pare = $gate->refreshTokens($refreshToken);
		if ($pare === false) {
		    if ($gate->tokenIsExpired()) {

		        return $this->prepareErrorResponse(
                    'Resource is unavailable',
                    ResponseCodeEnum::UNAUTHORIZED
                );
            } else {

		        return $this->prepareErrorResponse(
                    'Resource is unavailable',
                    ResponseCodeEnum::FORBIDDEN
                );
            }
		}

		return $this->prepareResponse([
            'token' => 'Bearer ' . $pare[0]->token,
            'refreshToken' => 'Bearer ' . $pare[1]->token,
        ]);
	}
}
