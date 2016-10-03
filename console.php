<?php

function echo_memory_usage()
{
    $mem_usage = memory_get_usage(true);

    if ($mem_usage < 1024) {
        echo $mem_usage . " bytes";
    } elseif ($mem_usage < 10485760) {
        echo round($mem_usage / 1024, 2) . " kilobytes";
    } else {
        echo round($mem_usage / 1048576, 2) . " megabytes";
    }

    echo PHP_EOL;
}
function echo_memory_peak_usage()
{
    $mem_usage = memory_get_peak_usage(true);

    if ($mem_usage < 1024) {
        echo $mem_usage . " bytes";
    } elseif ($mem_usage < 10485760) {
        echo round($mem_usage / 1024, 2) . " kilobytes";
    } else {
        echo round($mem_usage / 1048576, 2) . " megabytes";
    }

    echo PHP_EOL;
}

require_once __DIR__ . '/vendor/autoload.php';

$processor = new \CommunityStats\Crawler\GithubArchiveProcessor();

$processor->run();

echo PHP_EOL;
echo_memory_usage();
echo_memory_peak_usage();
echo PHP_EOL;
