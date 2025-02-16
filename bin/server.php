<?php

declare(strict_types=1);

header('Content-Type: application/json');

if ($_SERVER['SCRIPT_NAME'] === '/status/404') {
    http_response_code(404);
} elseif ($_SERVER['SCRIPT_NAME'] === '/absolute-redirect') {
    http_response_code(302);
} elseif ($_SERVER['SCRIPT_NAME'] === '/redirect-to') {
    http_response_code(302);
    header('Location: ' . $_GET['url']);
}

$content = [];

if ($_SERVER['SCRIPT_NAME'] === '/get') {
    $content['args'] = $_GET;
} elseif ($_SERVER['SCRIPT_NAME'] === '/post') {
    $content['form'] = $_POST;

    foreach ($_FILES as $file) {
        $content['files'][$file['name']] = file_get_contents($file['tmp_name']);
    }
} elseif (in_array($_SERVER['SCRIPT_NAME'], ['/put', '/delete'], true)) {
    $input = file_get_contents('php://input');

    if (str_starts_with($_SERVER['CONTENT_TYPE'], 'multipart/form-data; boundary=')) {
        $boundary = str_replace('multipart/form-data; boundary=', '', $_SERVER['CONTENT_TYPE']);

        $parts = explode("--{$boundary}", $input);

        foreach ($parts as $part) {
            $part = trim($part);

            if (str_starts_with($part, 'Content-Disposition: form-data;')) {
                if (str_contains($part, 'filename="')) {
                    $pattern = '/filename="(.*)";?/';
                    $key = 'files';
                } else {
                    $pattern = '/name="(.*)";?/';
                    $key = 'form';
                }

                preg_match($pattern, $part, $matches);
                $name = $matches[1];
                $value = preg_replace('/\A.*\r\n.*\r\n.*\r\n/', '', $part);
                $content[$key][$name] = $value;
            }
        }
    } else {
        parse_str($input, $output);
        $content['form'] = $output;
    }
}

echo json_encode($content);
