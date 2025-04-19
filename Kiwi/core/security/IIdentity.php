<?php

namespace core\security;

interface IIdentity{
    public function updateLastActivity();
    public function getLastActivity();
    public function getUserData();
    public function getSessionId();
    public function getIpAddress();
    public function getCreatedAt();
    public function setUsername($username);
    public function setRole($userRole);
    public function setUserDefinedData($userDefinedData);
    public function getUserRole();
    public function getUsername();
    public function getUserDefinedData();
    public function setSessionId($sessionId);
    public function setIpAddress($ipAddress);
    public function setCreatedAt($createdAt);
    public function getIdentityDetails(); // Returns all identity details in an array or object
    public function isSessionValid(); // Checks if the session is valid

}