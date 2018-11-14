<?php

namespace Gitphp;

class Git {
    private $repo;
    private $git;

    public function __construct($repo, $git = 'git') {
        $this->repo = $repo;
        $this->git = $git;
    }

    public function log($count, $format) {
        return $this->exec('log', ['-n', $count, '--pretty=' . $format]);
    }

    public function showRef($args) {
        return $this->exec('show-ref', $args);
    }

    public function diff($left_treeish, $right_treeish, $left_file, $right_file) {
        return $this->exec('diff', [$left_treeish, $right_treeish, '--', $left_file, $right_file]);

    }

    public function showNoPatch($fmt, $hash) {
        return $this->exec('show', ['--no-patch', '--pretty=' . $fmt, $hash]);
    }

    public function diffTree($left_hash, $right_hash) {
        return $this->exec('diff-tree', ['-r', $left_hash, $right_hash]);
    }

    protected function exec($func, $args) {
        \lib\Log::msg(\lib\Log::C_CTRL, \lib\Log::L_DEBUG,
            "git $func " . implode(
                ', ',
                array_map(
                    function ($arg) { return var_export($arg, true); },
                    $args
                )
            )
        );

        $args = array_merge([$this->git, '--git-dir=' . $this->repo, $func], $args);
        $args = array_map('escapeshellarg', $args);
        $cmd = implode(' ', $args) . ' 2>&1';
        exec($cmd, $out, $ret);
        return [$out, $ret];
    }
}
