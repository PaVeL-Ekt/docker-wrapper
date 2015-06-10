<?php

namespace PavelEkt\Wrappers\Docker\Abstracts;

use PavelEkt\Wrappers\Docker\Exceptions\Exception as DockerException;

abstract class DockerElementAbstract extends DockerAbstract
{
    protected $params = [
    ];

    protected $docker;

    protected function getShell()
    {
        return $this->docker->getShell($this);
    }

    public function __construct($docker, $params)
    {
        if ($docker instanceof DockerControllerAbstract) {
            $this->docker = $docker;
        } else {
            throw new DockerException('Invalid parent.', 'Invalid parent class.');
        }
        foreach ($params as $key => $value) {
            if (!isset(static::parseShellLineFields()[$key])) {
                unset ($params[$key]);
            }
        }
        parent::__construct($params);
    }

    public function __get($key)
    {
        $result = parent::__get($key);
        if (!is_null($result)) {
            return $result;
        }

        $matched = [];
        if (preg_match('/^short_([0-9a-z_-]*_id)$/', $key, $matched) && isset($this->params[$matched[1]])) {
            return substr($this->params[$matched[1]], 0, static::DOCKER_SHORT_ID_LENGTH);
        }

        return null;
    }

    static public function parseShellLineFields()
    {
        return [];
    }
}