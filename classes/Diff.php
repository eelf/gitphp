<?php

namespace Gitphp;

class Diff {
    private $left_treeish, $right_treeish, $left_file, $right_file, $Git;

    public function __construct($left_treeish, $right_treeish, $left_file, $right_file, Git $Git) {
        $this->left_treeish = $left_treeish;
        $this->right_treeish = $right_treeish;
        $this->left_file = $left_file;
        $this->right_file = $right_file;
        $this->Git = $Git;
    }

    public function read() {
        [$lines, $code] = $this->Git->diff($this->left_treeish, $this->right_treeish, $this->left_file, $this->right_file);
        if ($code) return [$lines, $code];
        $diff = [];
        $before = $after = 0;
        foreach ($lines as $line) {
            if (!$line
                // @todo handle \ No newline at end of file correcly
                || \lib\Str::hasPrefix($line, '\ No newline at end of file')
                || \lib\Str::hasPrefix($line, 'diff')
                || \lib\Str::hasPrefix($line, 'index')
                || \lib\Str::hasPrefix($line, 'new file')
                || ((\lib\Str::hasPrefix($line, '---') || \lib\Str::hasPrefix($line, '+++')) && !$before && !$after)
            ) continue;
            $first = $line[0];
            if ($first == '@') {
                // @todo new files tend to have after count absent, i.e. @@ -0,0 +1 @@
                if (!preg_match('#@@ -(?P<before>\d+)(,\d+)? \+(?P<after>\d+)(,\d+)? @@#', $line, $m)) throw new \Exception("line with @ doesn't match:$line");
                $before = (int)$m['before'];
                $after = (int)$m['after'];
                $line_entry = ['before' => $before, 'after' => $after, 'collapsed' => 1/*@todo if there is before*/];
            } else if ($first == '-') {
                $line_entry = ['before' => $before++, 'after' => 0];
            } else if ($first == '+') {
                $line_entry = ['before' => 0, 'after' => $after++];
            } else if ($first == ' ') {
                $line_entry = ['before' => $before++, 'after' => $after++];
            } else {
                throw new \Exception("unknown line:$line " . implode(' ', array_map('escapeshellarg', $args)));
            }
            $diff[] = $line_entry + ['line' => substr($line, 1)];
        }
        // @todo add collapsed after
        return [$diff, 0];
    }

}
