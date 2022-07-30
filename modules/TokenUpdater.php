<?php

namespace lx\auth\modules;

use lx;
use lx\Module;
use lx\ResponseCodeEnum;
use lx\HttpResponseInterface;

class TokenUpdater extends Module
{
    public function tryAuthenticate(): HttpResponseInterface
    {
        $gate = lx::$app->authenticationGate;
        if ($gate->authenticateUser()) {
            return $this->prepareResponse(
                lx::$app->userManager->getPublicData()
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
    
    public function refreshTokens($refreshToken): HttpResponseInterface
    {
        $gate = lx::$app->authenticationGate;

        $tokensMap = $gate->refreshTokens($refreshToken);
        if ($tokensMap === null) {
            if ($gate->isTokenExpired()) {
                return $this->prepareErrorResponse(
                    'expired',
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
            'userData' => lx::$app->userManager->getPublicData($tokensMap['user']),
            'accessToken' => $tokensMap['accessToken'],
            'refreshToken' => $tokensMap['refreshToken'],
        ]);
    }
}
