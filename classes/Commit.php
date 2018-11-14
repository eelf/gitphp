<?php

namespace Gitphp;

class Commit {
    public $hash, $tree, $parents,
        $author_name, $author_email, $author_ts,
        $commiter_name, $commiter_email, $commiter_ts,
        $subject, $diff;
    private $Git;

    public function __construct(string $hash, Git $Git) {
        $this->hash = $hash;
        $this->Git = $Git;
    }

    public function read() {
        $fmt = [
            ['%T', '%P'],
            ['%an', '%ae', '%at'],
            ['%cn', '%ce', '%ct'],
            ['%s']
        ];
        $fmt = implode('%n', array_map(function ($fmt) { return implode('%x09', $fmt);}, $fmt));
        [$lines, $exit_code] = $this->Git->showNoPatch($fmt, $this->hash);
        if ($exit_code) return false;
        [$this->tree, $this->parents] = explode("\t", $lines[0]);
        [$this->author_name, $this->author_email, $this->author_ts] = explode("\t", $lines[1]);
        [$this->commiter_name, $this->commiter_email, $this->commiter_ts] = explode("\t", $lines[2]);
        $this->subject = $lines[3];

        // @todo maybe many parents
        [$lines, $exit_code] = $this->Git->diffTree($this->parents, $this->hash);
        if ($exit_code) return false;

        foreach ($lines as $line) {
            $diff = [];
            [$line_info, $diff['name']] = explode("\t", ltrim($line, ':'));
            [$diff['mode_before'], $diff['mode_after'], $diff['hash_before'], $diff['hash_after'], $diff['change_type']] = explode(' ', $line_info);
            $this->diff[$diff['name']] = $diff;
        }

        return true;
    }
    
    public function toArray() {
        return [
            'hash' => $this->hash,
            'tree' => $this->tree,
            'parents' => $this->parents,
            'author_name' => $this->author_name, 'author_email' => $this->author_email, 'author_ts' => $this->author_ts,
            'commiter_name' => $this->commiter_name, 'commiter_email' => $this->commiter_email, 'commiter_ts' => $this->commiter_ts,
            'subject' => $this->subject,
            'diff' => $this->diff,
        ];
    }
}
