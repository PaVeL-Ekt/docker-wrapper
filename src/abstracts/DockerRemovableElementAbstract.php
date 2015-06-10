<?php

namespace PavelEkt\Wrappers\Docker\Abstracts;

use PavelEkt\Wrappers\Docker\Exceptions\Exception as DockerException;

abstract class DockerRemovableElementAbstract extends DockerElementAbstract
{
    protected $removed = false;

    protected function throwElementRemovedException()
    {
        throw new DockerException('Element removed.', 'Element has been removed.');
    }

    public function __get($key)
    {
        if ($this->removed) {
            $this->throwElementRemovedException();
        }
        return parent::__get($key);
    }

    public function __set($key, $value)
    {
        if ($this->removed) {
            $this->throwElementRemovedException();
        }
        parent::__set($key, $value);
    }

    public function __call($method, array $params)
    {
        if ($this->removed) {
            $this->throwElementRemovedException();
        }
        return parent::__call($method, $params);
    }

    protected function callRemove()
    {
        $this->removed = true;
        unset($this);
    }
}