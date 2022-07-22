<?php

namespace Zeero\Core\Auth;

use App\Models\User;


interface IAuthenticable
{
    /**
     * return a boolean if a user is logged
     *
     * @return boolean
     */
    function isLogged(): bool;

    /**
     * return a boolean if a user logged is admin ( level : 1 )
     *
     * @return boolean
     */
    function isAdmin(): bool;

    /**
     * return the current user if is logged, otherwise return null
     *
     * @return User|null
     */
    function getUser(): User|bool|null;
}
