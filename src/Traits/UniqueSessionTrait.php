<?php

namespace Obullo\Authentication\Traits;

use Obullo\Authentication\Storage\StorageInterface as Storage;

trait UniqueSessionTrait
{
     /**
     * Terminates multiple sessions.
     *
     * @return void
     */
    public function killSessions(Storage $storage)
    {
        $sessions = $storage->getUserSessions();

        if (empty($sessions) || sizeof($sessions) == 1) {  // If user have more than one session continue.
            return;
        }
        $sessionKeys = array();
        foreach ($sessions as $key => $val) {       // Keep the last session
            $sessionKeys[$val['__time']] = $key;
        }
        $lastSession = max(array_keys($sessionKeys));   // Get the highest integer time
        $protectedSession = $sessionKeys[$lastSession];
        unset($sessions[$protectedSession]);            // Don't touch the current session

        foreach (array_keys($sessions) as $loginID) {   // Destroy all other sessions
            $storage->killSession($loginID);
        }
    }
}
