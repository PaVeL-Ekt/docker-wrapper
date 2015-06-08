<?php

namespace PavelEkt\Wrappers;

class DockerContainer extends DockerExt
{
    const STATUS_ALL = 'all';
    const STATUS_RESTARTING = 'restarting';
    const STATUS_RUNNING = 'running';
    const STATUS_PAUSED = 'paused';
    const STATUS_EXITED = 'exited';

    protected function getImageParam()
    {
        if (!empty($this->params['subParams']['image'])) {
            return $this->params['docker']->getImage($this->params['subParams']['image']);
        }
        return null;
    }

    protected function callRemove($force = false)
    {
        $out = [];
        if (
            $this->params['shell']->exec(
                'docker rm '. ($force === true ? '-f ' : '') . $this->container_id,
                $out
            ) === 0
        ) {
            $this->params['deleted'] = true;
            $this->params['docker']->eventListener($this, 'remove');
            return true;
        } else {
            throw new DockerException(
                'Can`t remove container.',
                implode(PHP_EOL, $out['stderr']),
                DockerException::DOCKER_CONTAINER_ERRORS
            );
        }
    }

    static public function parseShellLineFields()
    {
        return [
            'container_id' => 'CONTAINER ID',
            'image' => 'IMAGE',
            'command' => 'COMMAND',
            'created' => 'CREATED',
            'status' => 'STATUS',
            'ports' => 'PORTS',
            'names' => 'NAMES',
        ];
    }

    static public function containerStatuses()
    {
        return [
            static::STATUS_RESTARTING,
            static::STATUS_RUNNING,
            static::STATUS_PAUSED,
            static::STATUS_EXITED,
        ];
    }

    static public function parseStatus($element)
    {
        $result = '';
        if ($element == static::STATUS_ALL) {
            $result .= ' -a';
        } elseif (
            is_string($element) &&
            in_array($element, static::containerStatuses()) &&
            strpos($result, $element) === false
        ) {
            $result .= ' -f status=' . $element;
        } elseif (
            is_array($element) &&
            is_string($element[0]) &&
            in_array($element[0], static::containerStatuses()) &&
            strpos($result, $element[0]) === false
        ) {
            $result .= ' -f status=' . $element[0] . ' -f ' . $element[0] . '=' . $element[1];
        }
        return $result;
    }
}
