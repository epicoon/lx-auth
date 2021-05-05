<?php

namespace lx\auth;

class Service extends \lx\Service
{
	public function getJsCoreExtension(): string
	{
		return "lx.__auth = function(request){
			let token = lx.Storage.get('lxauthtoken');
			if (!token) return;
			request.setRequestHeader('Authorization', token);
		};";
	}
}
