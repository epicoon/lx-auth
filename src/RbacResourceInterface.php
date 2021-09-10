<?php

namespace lx\auth;

interface RbacResourceInterface
{
    public function getPermissions(): array;
}
