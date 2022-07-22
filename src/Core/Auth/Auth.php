<?php

namespace Zeero\Core\Auth;

use App\Models\User;
use Zeero\Core\Router\URL;
use Zeero\facades\Session;

/**
 * 
 * Auth Class
 * 
 * 
 */
final class Auth implements IAuthenticable
{

    public function isLogged(): bool
    {
        $user_id = Session::get('user_id');

        if ($user_id == null) return false;

        return User::count('uuid = ?', [$user_id]);
    }

    public function isAdmin(): bool
    {
        if (!$this->isLogged()) return false;

        return $this->getUser()->level == 1;
    }


    public function getUser(): User|bool|null
    {
        if (!$this->isLogged()) return null;

        $user_id = Session::get('user_id');
        return User::findOne('uuid = ?', [$user_id]);
    }


    public function logout()
    {
        if ($this->isLogged()) {
            // update the user state
            $user = $this->getUser();
            if (is_object($user)) {
                $user->update(['online' => 0]);
            }
            // remove the current session
            Session::remove('user_id');
            unset($user);
            redirect('/login');
        } else {
            URL::back();
        }
    }
}
