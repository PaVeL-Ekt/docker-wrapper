<?php

namespace PavelEkt\Wrappers\Docker\Abstracts;

use PavelEkt\Wrappers\Shell;

class DockerControllerAbstract extends DockerAbstract
{
    protected $shell;

    public function __construct($shell, $params = [])
    {
        parent::__construct($params);
        if ($shell instanceof Shell) {
            $this->shell = $shell;
        } else {
            $this->shell = new Shell();
        }
    }

    public function getShell($caller = null)
    {
        return (!empty($caller) && $this->isChild($caller)) ? $this->shell : false;
    }
}