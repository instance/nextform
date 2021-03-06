<?php

namespace Abivia\NextForm\Contracts;

/**
 * Interface for access control.
 * @codeCoverageIgnore
 */
interface AccessInterface
{
    /**
     * Determine if the a user is allowed to perform an operation on an object.
     *
     * @param string $segment The segment that the requested object belongs to.
     * @param string $objectName The name of the object.
     * @param string $operation The operation we're asking permission for
     * (typically write, view, or hide).
     * @param string $user Overrides the current user to get another user's access.
     * @return bool
     */
    public function allows($segment, $objectName, $operation, $user = null) : bool;

    /**
     * Set a default user for subsequent access requests.
     * @param string $user The user identifier
     * @return \self
     */
    public function setUser($user);

}
