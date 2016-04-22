<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

/**
 * Execute command
 *
 * @param string $command
 * @return array command output each line as array element
 * @throw RuntimeException throw exception if exits with non-zero
 */
function execx($command)
{
    exec($command, $output, $rc);
    if ($rc) {
        throw new RuntimeException("$command exited with $rc");
    }

    return $output;
}

/**
 * Execute command, returning first line from it
 *
 * @param string $command
 * @return string
 */
function execl($command)
{
    $output = execx($command);

    return current($output);
}

/**
 * Submit SCM commit data to Eventum.
 *
 * @param array $params
 */
function scm_ping($params)
{
    global $PROGRAM, $eventum_url;

    $ping_url = $eventum_url . 'scm_ping.php?scm=' . $params['scm'];
    $status = json_post($ping_url, $params, 1);

    if ($status['code']) {
        throw new RuntimeException($status['message'], $status['code']);
    }

    $message = trim($status['message']);
    if (!$message) {
        return;
    }

    // prefix response with our name
    foreach (explode("\n", $message) as $line) {
        echo "$PROGRAM: $line\n";
    }
}

/**
 * Extract dir and file name from abspath
 *
 * @param string $abspath
 * @return array file dirname and basename
 */
function fileparts($abspath)
{
    // special for "dirname/" case, pathinfo would set dir to '.' and filename to 'dirname'
    $length = strlen($abspath);
    if ($abspath[$length - 1] == '/') {
        return array(rtrim($abspath, '/'), '');
    }

    $fi = pathinfo($abspath);

    return array($fi['dirname'], $fi['basename']);
}

/**
 * parse the commit message and get all issue numbers we can find
 *
 * @param string $commit_msg
 * @return array
 */
function match_issues($commit_msg)
{
    preg_match_all('/(?:issue|bug) ?:? ?#?(\d+)/i', $commit_msg, $matches);

    if (count($matches[1]) > 0) {
        return $matches[1];
    }

    return null;
}

/**
 * Fetch $url, return response and optionally unparsed headers array.
 *
 * @author Elan Ruusamäe <glen@delfi.ee>
 * @param string $url URL to request
 * @param array $params QueryString parameters to URL
 * @param boolean $headers = false
 * @return mixed
 * @return array|string
 */
function wget($url, $params, $headers = true)
{
    $url .= '?' . http_build_query($params, null, '&');

    // see if we can fopen
    $flag = ini_get('allow_url_fopen');
    if (!$flag) {
        throw new RuntimeException('allow_url_fopen is disabled');
    }

    // see if https is supported
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!in_array($scheme, stream_get_wrappers())) {
        throw new RuntimeException("$scheme:// scheme not supported. Load openssl php extension?");
    }

    $fp = @fopen($url, 'r');
    if (!$fp) {
        $error = error_get_last();
        throw new RuntimeException($error['message']);
    }

    if ($headers) {
        $meta = stream_get_meta_data($fp);
    }

    $data = '';
    while (!feof($fp)) {
        $data .= fread($fp, 4096);
    }
    fclose($fp);

    if ($headers) {
        return array($meta['wrapper_data'], $data);
    }

    return $data;
}

/**
 * POST json encoded data to $url
 *
 * @param string $url
 * @param array $data
 * @param bool $assoc
 * @return array|stdClass result with extra 'meta' key
 * @author Elan Ruusamäe <glen@delfi.ee>
 */
function json_post($url, $data, $assoc = false)
{
    // see if schema in url is supported
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!in_array($scheme, stream_get_wrappers())) {
        throw new RuntimeException("$scheme:// scheme not supported. Load openssl php extension?");
    }

    $body = json_encode($data);
    $headers = array(
        'Expect: ',
        'Content-Type: application/json',
        'Accept: application/json',
        'Content-Length: ' . strlen($body),
    );

    $options = array(
        'method' => 'POST',
        'content' => $body,
        'header' => implode("\r\n", $headers)
    );
    $options = array(
        $scheme => $options
    );

    $context = stream_context_create($options);

    $stream = @fopen($url, 'r', false, $context);
    if (!$stream) {
        $error = error_get_last();
        throw new RuntimeException($error['message']);
    }

    $result = stream_get_contents($stream);
    $meta = stream_get_meta_data($stream);
    fclose($stream);

    $response = json_decode($result, $assoc);
    if (!$response) {
        throw new InvalidArgumentException("Unable to decode: $result");
    }
    if ($assoc) {
        $response['meta'] = $meta;
        $response['raw'] = $result;
    } else {
        $response->raw = $result;
        $response->meta = $meta;
    }

    return $response;
}

/**
 * Sane getopt() ajusted from this post:
 * http://php.net/getopt#100573
 */
function _getopt($parameters)
{
    global $argv, $argc;

    $options = getopt($parameters);
    $pruneargv = array();
    foreach ($options as $option => $value) {
        foreach ($argv as $key => $chunk) {
            $regex = '/^' . (isset($option[1]) ? '--' : '-') . $option . '/';
            if ($chunk == $value && $argv[$key - 1][0] == '-' || preg_match($regex, $chunk)) {
                array_push($pruneargv, $key);
            }
        }
    }
    while ($key = array_pop($pruneargv)) {
        unset($argv[$key]);
    }

    // renumber $argv to be continuous
    $argv = array_values($argv);
    // reset $argc to be correct
    $argc = count($argv);

    return $options;
}
