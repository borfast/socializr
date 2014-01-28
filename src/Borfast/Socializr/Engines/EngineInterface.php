<?php

namespace Borfast\Socializr\Engines;

interface EngineInterface
{
	public function getUid();
	public function getProfile($uid = null);
}
