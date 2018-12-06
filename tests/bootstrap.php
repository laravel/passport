<?php

require __DIR__.'/../vendor/autoload.php';

function storage_path($file = null)
{
    return __DIR__.DIRECTORY_SEPARATOR.$file;
}
