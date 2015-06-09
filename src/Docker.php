<?php

namespace PavelEkt\Wrappers;

use PavelEkt\Wrappers\Shell as ShellWrapper;

/**
 * Класс для работы с докер контейнером.
 */
class Docker extends DockerExt
{
     /**
     * @var string $dockerCommand полный путь до исполняемого файла docker
     */
    public $dockerCommand = '/usr/bin/docker';


    public function parseLine($line, $fieldsList)
    {
        $result = [];
        foreach($fieldsList as $field => $values) {
            if (empty($values[1])) {
                $result[$field] = trim(substr($line, $values[0]));
            } else {
                $result[$field] = trim(substr($line, $values[0], $values[1]));
            }
        }
        return $result;
    }

    public function parseFieldsLine($line, $fieldsList)
    {
        $parsed = [];
        $result = [];
        preg_match_all('/(\w+[\s]?\w+)(\s{3,})?/i', $line, $parsed);
        $startPos = 0;
        foreach ($parsed[0] as $field) {
            $count = strlen($field);
            $field = trim($field);
            $key = array_search($field, $fieldsList);
            if ($key !== false) {
                $result[$key] = [$startPos, $count];
            }
            $startPos += $count;
        }
        // Фикс обрезания строки в последнем поле
        $field = array_pop($parsed[0]);
        $key = array_search($field, $fieldsList);
        $result[$key][1] = null;
        return $result;
    }

    public function __construct($shell = null)
    {
        $this->params['docker'] = $this;
        if ($shell instanceof ShellWrapper) {
            $this->params['shell'] = $shell;
        } elseif (is_string($shell) && is_dir($shell)) {
            $this->params['shell'] = new ShellWrapper($shell);
        } else {
            $this->params['shell'] = new ShellWrapper(__DIR__);
        }
    }

    public function getImages($nameOrId = null)
    {
        $out = [];
        $result = [];
        if (!empty($nameOrId)) {
            if (static::hasDockerId($nameOrId)) {
                $imageId = substr($nameOrId, 0, static::DOCKER_SHORT_ID_LENGTH);
            } else {
                $data = explode(':', $nameOrId, 2);
                $repository = $data[0];
                if (!empty($data[1])) {
                    $tag = $data[1];
                }
            }
        }
        if ($this->params['shell']->exec('docker images --no-trunc', $out) == 0) {
            $fieldsList = $this->parseFieldsLine($out['stdout'][0], DockerImage::parseShellLineFields());
            for ($i = 1, $ic = sizeof($out['stdout']); $i < $ic; ++$i) {
                $parsedLine = $this->parseLine($out['stdout'][$i], $fieldsList);
                if (isset($imageId)) {
                    if (substr($parsedLine['image_id'], 0, static::DOCKER_SHORT_ID_LENGTH) == $imageId) {
                        $result[] = new DockerImage($this, $parsedLine);
                    }
                } elseif (!empty($repository)) {
                    if ($parsedLine['repository'] == $repository) {
                        if ((!empty($tag) && $parsedLine['tag'] == $tag) || empty($tag)) {
                            $result[] = new DockerImage($this, $parsedLine);
                        }
                    }
                } else {
                    $result[] = new DockerImage($this, $parsedLine);
                }
            }
        }
        return $result;
    }

    public function getImage($nameOrId = null)
    {
        $result = $this->getImages($nameOrId);
        if (!empty($result) && is_array($result)) {
            return $result[0];
        }
        return null;
    }

    public function getContainers($status = DockerContainer::STATUS_RUNNING, $container = null, $image = null)
    {
        $out = [];
        $result = [];

        $param = '';
        if (is_array($status)) {
            foreach ($status as $element) {
                $res = DockerContainer::parseStatus($element);
                if ($res == ' -a') {
                    $param .= ' -a';
                    break;
                } else {
                    $param .= $res;
                }
            }
        } elseif (is_string($status)) {
            $param = DockerContainer::parseStatus($status);
        }

        if (!empty($container) && static::hasDockerId($container)) {
            $containerId = substr($container, 0, static::DOCKER_SHORT_ID_LENGTH);
        } else {
            $container = strtolower($container);
        }

        if (!empty($image)) {
            if (!($image instanceof DockerImage)) {
                $image = $this->getImage($image);
            }
        }

        if ($this->params['shell']->exec('docker ps' . $param . ' --no-trunc', $out) == 0) {
            $fieldsList = $this->parseFieldsLine($out['stdout'][0], DockerContainer::parseShellLineFields());
            for ($i = 1, $ic = sizeof($out['stdout']); $i < $ic; ++$i) {
                $parsedLine = $this->parseLine($out['stdout'][$i], $fieldsList);
                if (!empty($containerId)) {
                    if (substr($parsedLine['container_id'], 0, static::DOCKER_SHORT_ID_LENGTH) == $containerId) {
                        if (
                            empty($image) ||
                            (
                                !empty($image) &&
                                (
                                    $parsedLine['image'] == $image->repository . ':' . $image->tag ||
                                    substr($parsedLine['image'], 0, 12) == $image->short_image_id
                                )
                            )
                        ) {
                            $result[] = new DockerContainer($this, $parsedLine);
                        }
                    }
                } elseif (!empty($container)) {
                    if (strtolower($parsedLine['names']) == $container) {
                        if (
                            empty($image) ||
                            (
                                !empty($image) &&
                                (
                                    $parsedLine['image'] == $image->repository . ':' . $image->tag ||
                                    substr($parsedLine['image'], 0, 12) == $image->short_image_id
                                )
                            )
                        ) {
                            $result[] = new DockerContainer($this, $parsedLine);
                        }
                    }
                } else {
                    if (
                        empty($image) ||
                        (
                            !empty($image) &&
                            (
                                $parsedLine['image'] == $image->repository . ':' . $image->tag ||
                                substr($parsedLine['image'], 0, 12) == $image->short_image_id
                            )
                        )
                    ) {
                        $result[] = new DockerContainer($this, $parsedLine);
                    }
                }
            }
        }
        return $result;
    }

    public function getContainer($container = null, $image = null)
    {
        $result = $this->getContainers(DockerContainer::STATUS_ALL, $container, $image);
        if (!empty($result) && is_array($result)) {
            return $result[0];
        }
        return null;
    }

    public function getShell($object)
    {
        if ($object instanceof DockerExt) {
            return $this->params['shell'];
        }
        return null;
    }

    public function removeContainer($container, $force = false)
    {
        if ($container instanceof DockerContainer) {
            return $container->remove($force);
        }
        return false;
    }

    public function removeImage($image, $force = false)
    {
        if ($image instanceof DockerImage) {
            return $image->remove($force);
        }
        return false;
    }

    public function runImage($image, $script = null, $params = [], $scriptParams = [], &$output = null)
    {
        if (!($image instanceof DockerImage)) {
            $image = $this->getImage($image);
        }
        if ($image instanceof DockerImage) {
            return $image->run($script, $params, $scriptParams, $output);
        }

        return false;
    }

    public function login($userName, $password, $email = "")
    {
        if (
            $this->params['shell']->exec(
                'docker login -u ' . $userName . ' -p ' . $password . ' -e ' . $email, $out
            ) == 0
        ) {
            return true;
        }
        return false;
    }

    public function pullImage($imageName)
    {
        if (
            $this->params['shell']->exec(
                'docker pull ' . $imageName, $out
            ) == 0
        ) {
            return true;
        }
        return false;
    }

    public function eventListener(&$object, $event)
    {
        if ($object instanceof DockerExt) {
            if ($event == 'remove') {
                $object = null;
            }
        }
    }
}
