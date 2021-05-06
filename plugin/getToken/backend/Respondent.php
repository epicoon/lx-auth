<?php

namespace lx\auth\plugin\getToken\backend;

use lx\auth\OAuth2AuthenticationGate;
use lx\AuthenticationInterface;
use lx\ResponseCodeEnum;
use lx\ResponseInterface;

class Respondent extends \lx\Respondent
{
	public function tryAuthenticate(): ResponseInterface
	{
	    /** @var AuthenticationInterface $gate */
		$gate = $this->app->authenticationGate;
		if ($gate->authenticateUser()) {
		    return $this->prepareResponse(
		        $this->app->userManager->getPublicData()
            );
		}

		if ($gate->isTokenExpired()) {
		    return $this->prepareErrorResponse('expired', ResponseCodeEnum::UNAUTHORIZED);
		}
		
		if ($gate->isTokenNotFound()) {
            return $this->prepareErrorResponse('token not found', ResponseCodeEnum::UNAUTHORIZED);
        }

		return $this->prepareErrorResponse('Internal server error', ResponseCodeEnum::SERVER_ERROR);
	}
	
	public function refreshTokens($refreshToken): ResponseInterface
	{
        /** @var AuthenticationInterface $gate */
		$gate = $this->app->authenticationGate;

		$pare = $gate->refreshTokens($refreshToken);
		if ($pare === null) {
		    if ($gate->isTokenExpired()) {
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
