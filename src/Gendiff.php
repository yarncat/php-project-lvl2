<?php

namespace Differ\Gendiff;

use function Differ\Parsers\parse;
use function Differ\Formatters\Renders\render;

function genDiff($pathToFirstFile, $pathToSecondFile, $format)
{
    $data1 = getData($pathToFirstFile);
    $data2 = getData($pathToSecondFile);

    $tree = buildTree($data1, $data2);
    return render($tree, $format);
}

function getData($pathToFile)
{
    if (!file_exists($pathToFile)) {
        throw new \Exception("File '{$pathToFile}' is not exist or the specified path is incorrect");
    }
    $data = file_get_contents($pathToFile);
    $fileExtension = pathinfo($pathToFile, PATHINFO_EXTENSION);
    return parse($data, $fileExtension);
}

function buildTree($data1, $data2)
{
    $data1 = (array)$data1;
    $data2 = (array)$data2;
    $keys = array_keys(array_merge($data1, $data2));
    sort($keys);

    return array_map(function ($key) use ($data1, $data2) {
        if (!array_key_exists($key, $data2)) {
            return ['key' => $key, 'status' => 'deleted', 'value' => $data1[$key]];
        }
        if (!array_key_exists($key, $data1)) {
            return ['key' => $key, 'status' => 'added', 'value' => $data2[$key]];
        }
        if (is_object($data1[$key]) && is_object($data2[$key])) {
            return ['key' => $key, 'status' => 'parent', 'children' => buildTree($data1[$key], $data2[$key])];
        } elseif (
            ((is_array($data1[$key]) && is_array($data2[$key])) && ($data1[$key] == $data2[$key]))
            || ($data1[$key] === $data2[$key])
        ) {
            return ['key' => $key, 'status' => 'unchanged', 'value' => $data1[$key]];
        } else {
            return ['key' => $key, 'status' => 'changed', 'oldValue' => $data1[$key], 'newValue' => $data2[$key]];
        }
    }, $keys);
}
