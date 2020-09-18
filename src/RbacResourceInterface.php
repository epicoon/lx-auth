<?php

namespace lx\auth;

/**
 * Interface RbacResource
 * @package lx\auth
 */
interface RbacResourceInterface
{
    /**
     * @return array
     */
    public function getPermissions();
}
