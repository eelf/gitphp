<?php

function run($cmd, $args = []) {
    $args = array_map('escapeshellarg', $args);
    $cmd = implode(' ', array_merge([$cmd], $args));
    $timer = microtime(true);
    $ret = proc_close(proc_open($cmd, [], $pp));
    echo "exec $cmd = $ret in " . number_format((microtime(true) - $timer) * 1e6) . " us\n";
}

function find_yarn_in_path($path) {
    return array_reduce($path, function ($carry, $item) {
        return file_exists($item) ? $item : $carry;
    }, 'yarn');
}

function dir_iterator($dir) {
    if (!$dh = opendir($dir)) return;
    while ($e = readdir($dh)) {
        if (in_array($e, ['.', '..'])) continue;
        $path = "$dir/$e";
        if (!is_link($path) && is_dir($path)) yield from dir_iterator($path);
        yield $path;
    }
}

function rm_recursive($path) {
    if (!is_link($path) && is_dir($path)) {
        foreach (dir_iterator($path) as $file) {
            if (!is_link($file) && is_dir($file)) rmdir($file);
            else {
                echo "unlink($file)\n";
                unlink($file);
            }
        }
        echo "rmdir($path)\n";
        rmdir($path);
    } else if (file_exists($path)) {
        echo "unlink $path\n";
        unlink($path);
    }
}

if (!in_array($mode = $argv[1] ?? null, ['production', 'development', 'clean'])) {
    echo "usage: build <production|development|clean>\n";
    exit(0);
}

chdir(__DIR__);

if ($mode == 'clean') {
    foreach (['.npmrc', 'webpack.config.js', '../www/app.js.map', 'yarn.lock', 'yarn-error.log', 'package.json', 'node_modules'] as $file) {
        rm_recursive($file);
    }
    exit(0);
}

$yarn = find_yarn_in_path(['/usr/bin/yarn']);
$global_nm_prefix = "$_SERVER[HOME]/nodem";
$global_nm_dir = "$global_nm_prefix/lib/node_modules";


file_put_contents('.npmrc', "prefix = $global_nm_prefix");

$packages = [
    'webpack-cli',
    'webpack',
    'babel-loader@7',
    'babel-core',
    'babel-preset-react',
    'babel-preset-stage-2',
    'react',
    'react-dom',
    'react-bootstrap',
    'eventemitter3',
    'https://github.com/ninedays-io/fast-route',
];

run($yarn, array_merge(['add', '--modules-folder', $global_nm_dir, '--dev'], $packages));

$packages_clean = array_map(function ($e) {
    return basename(explode('@', $e)[0]);
}, $packages);

foreach ($packages_clean as $package) {
    run("cd $global_nm_dir/$package; $yarn link");
}

run($yarn, array_merge(['link'], $packages_clean));

$dev_tool = $mode == 'development' ? 'devtool: \'source-map\',' : '';

$webpack_conf = <<<NOW
module.exports = {
    mode: '$mode',
    $dev_tool
    entry: './src/index.js',
    output: {
        path: require("path").join(__dirname, '/../www'),
        filename: 'app.js'
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['react', 'stage-2']
                    }
                }
            }
        ]
    }
};
NOW;

file_put_contents('webpack.config.js', $webpack_conf);
