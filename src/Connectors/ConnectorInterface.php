<?php

namespace Borfast\Socializr\Connectors;

interface ConnectorInterface
{
    public function getAuthorizationUri(array $params = array());
    public function storeOauthToken($params);
    public function getSessionData();

    public function get($path, $params = array());

    public function getProfile();
    public function getPage();
    public function getGroup();
    public function getPages();
    public function getGroups();

    public function getUid();
    public function getStats();
}
