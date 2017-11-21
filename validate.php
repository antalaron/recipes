<?php

$hasError = false;
$errors = [];

if (false === $config = getJson('config.json')) {
    foreach ($errors as $error) {
        echo $error."\n";
    }

    exit(1);
}

if (!array_key_exists('skeletons', $config) || !is_array($config['skeletons'])) {
    echo "Ivalid:\n - No recepie in config.json\n";

    exit(1);
}

foreach ($config['skeletons'] as $key => $array) {
    if (!is_array($array)) {
        $hasError = true;
        $errors[] = ' - Invalid config for '.$key;

        continue;
    }

    if (!array_key_exists('current-version', $array)) {
        $hasError = true;
        $errors[] = ' - No current-version for '.$key;
    }

    if (!array_key_exists('versions', $array) || !is_array($array['versions'])) {
        $hasError = true;
        $errors[] = ' - No versions for '.$key;

        continue;
    }

    if (!in_array($array['current-version'], $array['versions'])) {
        $hasError = true;
        $errors[] = ' - Current version for '.$key.' ('.$array['current-version'].') is not in versions';
    }

    if (!array_key_exists('description', $array) || !is_string($array['description'])) {
        $hasError = true;
        $errors[] = ' - No description for '.$key;
    } elseif (24 > strlen($array['description'])) {
        $hasError = true;
        $errors[] = ' - Description for '.$key.' has to be at least 24 character long';
    }

    foreach ($array['versions'] as $version) {
        if (false === $versionConfig = getJson($skeletonFile = $key.'/'.$version.'/config.json')) {
            continue;
        }

        if (!array_key_exists('variables', $versionConfig) || !is_array($versionConfig['variables'])) {
            $hasError = true;
            $errors[] = ' - No variables in '.$skeletonFile;

            continue;
        }

        foreach ($versionConfig['variables'] as $variable => $variableConfig) {
            if (!array_key_exists('title', $variableConfig)) {
                $hasError = true;
                $errors[] = ' - No title for '.$variable.' in '.$skeletonFile;
            }

            if (!array_key_exists('default', $variableConfig)) {
                $hasError = true;
                $errors[] = ' - No default for '.$variable.' in '.$skeletonFile;
            }

            if (!array_key_exists('files', $variableConfig) || !is_array($variableConfig['files'])) {
                $hasError = true;
                $errors[] = ' - No files for '.$variable.' in '.$skeletonFile;

                continue;
            }

            foreach ($variableConfig['files'] as $fileForVariable) {
                if (!file_exists(__DIR__.'/'.$key.'/'.$version.'/src/'.$fileForVariable)) {
                    $hasError = true;
                    $errors[] = ' - File '.$key.'/'.$version.'/src/'.$fileForVariable.' does not exist, however variable '.$variable.' requests it in '.$skeletonFile;
                }
            }
        }
    }
}

if ($hasError) {
    $errors = array_unique($errors);

    echo "Ivalid:\n";
    foreach ($errors as $error) {
        echo $error."\n";
    }

    exit(1);
}

echo "Valid\n";

function getJson($file)
{
    global $hasError;
    global $errors;

    if (!file_exists(__DIR__.'/'.$file)) {
        $hasError = true;
        $errors[] = ' - File '.$file.' does not exist';

        return false;
    }

    $content = json_decode(file_get_contents($file), true);
    if (JSON_ERROR_NONE !== json_last_error()) {
        $hasError = true;
        $errors[] = ' - File '.$file.' is invalid JSON';

        return false;
    }

    return $content;
}
