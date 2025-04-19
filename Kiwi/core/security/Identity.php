<?php
namespace core\security;

class Identity implements IIdentity {
    private $userData; // Store user-related data
    private $sessionId; // Store session ID
    private $ipAddress; // Store IP address
    private $createdAt; // Store creation timestamp
    private $lastActivity; // Store last activity timestamp

    private int $SESSION_TIMEOUT = 1800;

    public function __construct($username, $userRole, $userDefinedData, $sessionId, $ipAddress, $createdAt) {
        $this->userData = [
            'username' => $username,
            'userRole' => $userRole,
            'userDefinedData' => $userDefinedData // Store additional user-defined data
        ];
        $this->sessionId = $sessionId;
        $this->ipAddress = $ipAddress;
        $this->createdAt = $createdAt;
        $this->lastActivity = $createdAt; // Initialized with creation time
    }

    public function updateLastActivity(): void
    {
        $this->lastActivity = time();
    }

    public function getLastActivity() {
        return $this->lastActivity;
    }

    public function getUserData(): array
    {
        return $this->userData;
    }

    public function getSessionId() {
        return $this->sessionId;
    }

    public function getIpAddress() {
        return $this->ipAddress;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function setUsername($username): void
    {
        $this->userData['username'] = $username;
    }

    public function setRole($userRole): void
    {
        $this->userData['userRole'] = $userRole;
    }

    public function setUserDefinedData($userDefinedData): void
    {
        $this->userData['userDefinedData'] = $userDefinedData;
    }

    // Additional methods for identity management
    public function getUserRole()
    {
        return $this->userData['userRole'];
    }

    public function getUsername()
    {
        return $this->userData['username'];
    }

    public function getUserDefinedData()
    {
        return $this->userData['userDefinedData'];
    }

    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getIdentityDetails()
    {
        return [
            'userData' => $this->userData,
            'sessionId' => $this->sessionId,
            'ipAddress' => $this->ipAddress,
            'createdAt' => $this->createdAt,
            'lastActivity' => $this->lastActivity
        ];
    }

    public function isSessionValid()
    {
        // Logic to check if the session is valid
        // Example: Compare session ID, IP address, and last activity time
        return ($this->sessionId !== null && $this->ipAddress !== null && time() - $this->lastActivity <= $this->SESSION_TIMEOUT);
    }
}

