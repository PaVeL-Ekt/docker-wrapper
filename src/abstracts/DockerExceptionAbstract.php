<?php

namespace PavelEkt\Wrappers\Docker\Abstracts;

abstract class DockerExceptionAbstract extends \Exception
{
    const DOCKER_NO_GROUP_ERRORS = -1;
    const DOCKER_ERRORS = 0;
    const DOCKER_IMAGE_ERRORS = 1;
    const DOCKER_CONTAINER_ERRORS = 2;

    protected $title;
    protected $cause;
    protected $group;

    public function __construct($title, $cause, $group = self::DOCKER_NO_GROUP_ERRORS, $code = 0)
    {
        parent::__construct($title . PHP_EOL . $cause, $code, $this);
        $this->title = $title;
        $this->cause = $cause;
        $this->group = $group;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getCause()
    {
        return $this->cause;
    }

    public function getGroup()
    {
        return $this->group;
    }
}