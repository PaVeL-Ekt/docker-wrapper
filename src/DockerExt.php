<?php

namespace PavelEkt\Wrappers;

class DockerExt
{
    const DOCKER_SHORT_ID_LENGTH = 12;
    const DOCKER_ID_LENGTH = 64;

    protected $params = [
        'docker' => null,
        'shell' => null,
        'deleted' => false,
        'subParams' => [],
    ];

    public function __construct($docker, $params)
    {
        if ($docker instanceof Docker) {
            $this->params['docker'] = $docker;
        } else {
            $this->params['docker'] = new Docker();
        }
        $this->params['shell'] = new Shell($this->params['docker']->getShell($this)->workDirectory);
        foreach (static::parseShellLineFields() as $key => $value) {
            if (isset($params[$key])) {
                $this->params['subParams'][$key] = $params[$key];
            }
        }
    }

    public function __get($key)
    {
        if ($this->params['deleted'] === true) {
            throw new DockerException('Element not available.', 'This element was deleted.');
        }
        $key = strtolower($key);
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }
        $methodName = 'get' . ucfirst($key) . 'Param';
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }
        if (isset($this->params['subParams'][$key])) {
            return $this->params['subParams'][$key];
        }
        $matched = [];
        if (preg_match('/^short_([0-9a-z_-]*_id)$/', $key, $matched) && isset($this->params['subParams'][$matched[1]])) {
            return substr($this->params['subParams'][$matched[1]], 0, static::DOCKER_SHORT_ID_LENGTH);
        }
        if (method_exists($this, 'get' . $key)) {
            return $this->$key;
        }
        return null;
    }

    public function __set($key, $value)
    {
        if ($this->params['deleted'] === true) {
            throw new DockerException('Element not available.', 'This element was deleted.');
        }
        $key = strtolower($key);
        if (is_array($value) && !empty($value) && ($value[0] instanceof Docker)) {
            if (isset($this->params['subParams'][$key])) {
                $this->params['subParams'] = $value;
            }
        }
    }

    public function __call($method, array $params)
    {
        if ($this->params['deleted'] === true) {
            throw new DockerException('Element not available.', 'This element was deleted.');
        }
        if (method_exists($this, 'call' . ucfirst($method))) {
            return call_user_func_array(array($this, 'call' . ucfirst($method)), $params);
        }
        throw new DockerException('Method unavailable.', 'Method ' . $method . ' not exists in class ' . __CLASS__);
    }

    static protected function hasDockerId($line)
    {
        return preg_match('/(^[0-9a-f]{12}$)|(^[0-9a-f]{64}$)/i', $line);
    }

    static public function parseShellLineFields()
    {
        return [];
    }
}