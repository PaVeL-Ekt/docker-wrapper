<?php

namespace PavelEkt\Wrappers;

class DockerImage extends DockerExt
{
    protected function callRemove($force = false) {
        $out = [];
        if (
            $this->params['shell']->exec(
                'docker rmi '. ($force === true ? '-f ' : '') . $this->image_id,
                $out
            ) === 0
        ) {
            $this->params['deleted'] = true;
            $this->params['docker']->eventListener($this, 'remove');
            return true;
        } else {
            throw new DockerException(
                'Can`t remove image.',
                implode(PHP_EOL, $out['stderr']),
                DockerException::DOCKER_IMAGE_ERRORS
            );
        }
    }

    public function run($script = null, array $params = [], array $scriptParams = [], &$output = null)
    {
        $out = [];
        $cmd = 'docker run';
        if (!empty($params['mount-dirs'])) {
            foreach ($params['mount-dirs'] as $rootDir => $dockerDir) {
                if (is_dir($rootDir)) {
                    $cmd .= ' -v ' . $rootDir . ':' . $dockerDir;
                }
            }
        }
        if (!empty($params['name'])) {
            $cmd .= ' --name ' . $params['name'];
        }
        if (!empty($params['cap-add'])) {
            $cmd .= ' --cap-add=' . $params['cap-add'];
        }
        // Add image
        $cmd .= ' ' . $this->short_image_id;
        if (!empty($script)) {
            $cmd .= ' ' . $script;
        }
        foreach ($scriptParams as $key => $value) {
            $cmd .= ' ' . $key . '=' . $value;
        }

        if ($this->params['shell']->exec($cmd, $out) !== 0) {
            throw new DockerException(
                'Can`t run image.',
                implode(
                    PHP_EOL,
                    [
                        'STDOUT:',
                        implode(PHP_EOL, $out['stdout']),
                        'STDERR:',
                        implode(PHP_EOL, $out['stderr']),
                    ]
                ),
                DockerException::DOCKER_IMAGE_ERRORS
            );
        }
        if (isset($output)) {
            $output = $out['stdout'];
        }
        return true;
    }

    static public function parseShellLineFields()
    {
        return [
            'repository' => 'REPOSITORY',
            'tag' => 'TAG',
            'image_id' => 'IMAGE ID',
            'created' => 'CREATED',
            'virtual_size' => 'VIRTUAL SIZE',
        ];
    }
}