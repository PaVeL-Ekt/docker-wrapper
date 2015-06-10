<?php

namespace PavelEkt\Wrappers\Docker\Abstracts;

use PavelEkt\Wrappers\Docker\Exceptions\Exception as DockerException;

abstract class DockerAbstract
{
    const DOCKER_SHORT_ID_LENGTH = 12;
    const DOCKER_ID_LENGTH = 64;

    protected $params = [];
    protected $id = '';

    protected $paramRules = [
        'ro' => [],
        'wo' => [],
    ];

    public function __construct($params)
    {
        $this->id = md5(time() . 'docker');
        $this->params = $params;
    }

    protected function isChild($element)
    {
        return $element instanceof DockerAbstract;
    }

    public function __get($key)
    {
        $methodName = 'get' . ucfirst($key);
        if (method_exists($this, $methodName)) {
            return call_user_func([$this, $methodName]);
        }
        if (isset($this->params[$key]) && !in_array($key, $this->paramRules['wo'])) {
            return $this->params[$key];
        }
        return null;
    }

    public function __set($key, $value)
    {
        $methodName = 'set' . ucfirst($key);
        if (method_exists($this, $methodName)) {
            call_user_func([$this, $methodName], $value);
        }
        if (isset($this->params[$key]) && !in_array($key, $this->paramRules['ro'])) {
            $this->params[$key] = $value;
        }
    }

    public function __call($method, array $params)
    {
        $methodName = 'call' . ucfirst($method);
        if (method_exists($this, $methodName)) {
            return call_user_func_array(array($this, $methodName), $params);
        }
        throw new DockerException('Method unavailable.', 'Method ' . $method . ' not exists in class ' . __CLASS__);
    }

    static protected function hasDockerId($line)
    {
        return preg_match('/(^[0-9a-f]{12}$)|(^[0-9a-f]{64}$)/i', $line);
    }
}