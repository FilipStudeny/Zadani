<?php

namespace core\security;

class AuthenticationHandler
{
    private IdentityManager $identityManager;

    public function __construct(IdentityManager $identityManager)
    {
        $this->identityManager = $identityManager;
    }

    public function createSession($username, $userRole, $userDefinedData, $sessionId, $ipAddress, $createdAt): Identity
    {
        return $this->identityManager->createIdentity($username, $userRole, $userDefinedData, $sessionId, $ipAddress, $createdAt);
    }

    public function deleteSession($sessionId): bool
    {
        return $this->identityManager->deleteIdentity($sessionId);
    }

    public function modifySession(Identity $identity): null
    {
        return $this->identityManager->updateIdentity($identity);
    }
}