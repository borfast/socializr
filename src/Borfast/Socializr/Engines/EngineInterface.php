<?php

namespace Borfast\Socializr\Engines;

interface EngineInterface
{
    public function getAuthorizationUri(array $params = array());
    public function storeOauthToken($params);
    public function getSessionData();

    public function get($path, $params = array());

    public function getProfile($uid = null);
    public function getPage($uid = null);
    public function getGroup($uid = null);

    public function getUid();
    public function getStats($uid = null);
}
