<?php

namespace Gitphp;

class Controller {
    /** @var UserSession */
    private $UserSession;
    /** @var \lib\Request */
    private $Request;
    /** @var \lib\Response */
    private $Response;
    public function __construct($Request, $Response) {
        $this->Request = $Request;
        $this->Response = $Response;
    }

    private function getRepositories() {
        return \lib\Context::getConfig('repositories');
    }

    public function run() {
        $auth_needed = false;

        if ($auth_needed) {
            $this->UserSession = new UserSession($this->Request, $this->Response);
            if (!$this->UserSession->getId()) {
                return $this->responseGeneric(1, 'Unauthorized');
            }
        }

        $post_data = json_decode($this->Request->postData(), true);
        $method = $post_data['method'] ?? null;

        if ($method == 'init') {
            $this->responseGeneric(0, 'OK');
        } else if ($method == 'list_repositories') {
            $this->listRepos();
        } else if ($method == 'show_repo') {
            $repo = $post_data['name'] ?? null;
            $this->showRepo($repo);
        } else if ($method == 'show_commit') {
            $repo = $post_data['repo'] ?? null;
            $hash = $post_data['hash'] ?? null;
            $this->showCommit($repo, $hash);
        } else if ($method == 'get_diff') {
            $repo = $post_data['repo'] ?? null;
            $hash = $post_data['hash'] ?? null;
            $parent = $post_data['parent'] ?? null;
            $names = $post_data['names'] ?? [];
            $this->getDiff($repo, $hash, $parent, $names);
        } else {
            $this->responseGeneric(1, 'unknown method:' . $this->Request->postData());
        }
    }

    protected function listRepos() {
        $list = array_values(
            array_map(
                function ($e) {
                    return [
                        'name' => $e,
                    ];
                },
                array_filter(
                    scandir(self::getRepositories()),
                    function ($e) {
                        $full_path = self::getRepositories() . "/$e";
                        return $e != '.' && $e != '..' && is_dir($full_path);
                    }
                )
            )
        );
        $this->response(['type' => 'list_repositories', 'list' => $list ?? []]);
    }

    protected function showRepo($repo) {
        $Git = new Git(self::getRepositories() . '/' . $repo);
        [$log, $ret] = $Git->log(50, '%H%x09%at%x09%an%x09%s');
        if ($ret) return $this->responseGeneric(1, 'error:' . implode("\n", $log));
        $log = array_map(
            function ($e) {
                [$hash, $time, $author, $subject] = explode("\t", $e, 4);
                return ['hash' => $hash, 'time' => $time, 'author' => $author, 'subject' => $subject];
            },
            $log
        );

        [$heads, $ret] = $Git->showRef(['--heads', '--tags', '--dereference']);
        if ($ret) return $this->responseGeneric(1, 'error:' . implode("\n", $log));

        $this->response(['log' => $log, 'heads' => $heads, 'repo' => $repo]);
    }

    protected function showCommit($repo, $hash) {
        $Git = new Git(self::getRepositories() . '/' . $repo);
        $Commit = new Commit($hash, $Git);
        if (!$Commit->read()) return $this->responseGeneric(1, 'error getting commit');


        $this->response($Commit->toArray() + ['repo' => $repo, 'hash' => $hash]);
    }

    protected function getDiff($repo, $hash, $parent, $names) {
        $Git = new Git(self::getRepositories() . '/' . $repo);
        $diffs = [];
        foreach ($names as $name) {
            $Diff = new Diff($parent, $hash, $name, $name, $Git);
            try {
                [$lines, $exit_code] = $Diff->read();
            } catch (\Exception $e) {
                return $this->responseGeneric(1, (string)$e);
            }
            if ($exit_code) return $this->responseGeneric(1, 'error getting diff for ' . $name);
            $diffs[$name] = $lines;
        }
        $this->response(['repo' => $repo, 'hash' => $hash, 'parent' => $parent, 'diffs' => $diffs]);
    }

    protected function responseGeneric($code, $text) {
        $this->response(['type' => 'generic', 'error_code' => $code, 'error_text' => $text]);
    }
    protected function response($json) {
        $json += ['request' => $this->Request->postData(), 'ss' => \lib\StatSlow::getErrors()];
        $this->Response->header('Content-Type', 'application/json');
        $this->Response->body(json_encode($json));
        if ($this->UserSession) $this->UserSession->finish();
        $this->Response->out();
    }
}
