<?php

namespace THCFrame\Security\Authorization;

use THCFrame\Security\UserInterface;

/**
 * AuthorizationInterface ensure that authorization class will have isGranted method
 */
interface AuthorizationInterface
{
    public function isGranted(UserInterface $user, $requiredRole);
}
