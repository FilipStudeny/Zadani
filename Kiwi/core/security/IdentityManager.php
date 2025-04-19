<?php

namespace core\security;

class IdentityManager
{
    private $identityStorage = [];
    private const SESSION_TIMEOUT = 3600; // Session timeout in seconds (1 hour)

    public function createIdentity($username, $userRole, $userDefinedData, $sessionId, $ipAddress, $createdAt)
    {
        $newIdentity = new Identity($username, $userRole, $userDefinedData, $sessionId, $ipAddress, $createdAt);
        $this->identityStorage[$sessionId] = [
            'identity' => $newIdentity,
            'lastActivity' => time(),
        ];
        return $newIdentity;
    }

    public function getIdentity($sessionId)
    {
        if ($this->isSessionValid($sessionId)) {
            $this->updateSessionActivity($sessionId);
            return $this->identityStorage[$sessionId]['identity'];
        }
        return null;
    }

    public function updateIdentity(Identity $identity)
    {
        if (isset($this->identityStorage[$identity->getSessionId()])) {
            $this->identityStorage[$identity->getSessionId()]['identity'] = $identity;
            $this->updateSessionActivity($identity->getSessionId());
        }
    }

    public function deleteIdentity($sessionId)
    {
        if (isset($this->identityStorage[$sessionId])) {
            unset($this->identityStorage[$sessionId]);
            return true;
        }
        return false;
    }

    public function extendSession($sessionId): bool
    {
        if ($this->isSessionValid($sessionId)) {
            $this->updateSessionActivity($sessionId);
            return true;
        }
        return false;
    }

    private function isSessionValid($sessionId): bool
    {
        return isset($this->identityStorage[$sessionId]) &&
            (time() - $this->identityStorage[$sessionId]['lastActivity'] <= self::SESSION_TIMEOUT);
    }

    private function updateSessionActivity($sessionId): void
    {
        $this->identityStorage[$sessionId]['lastActivity'] = time();
    }
}