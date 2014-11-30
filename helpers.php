<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2014 Eventum Team.                                     |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * Cxecute command
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
 * Submit SCM commit data to Eventum.
 *
 * @param array $params
 */
function scm_ping($params)
{
    global $PROGRAM, $eventum_url;

    $ping_url = $eventum_url . "scm_ping.php";
    $params['json'] = 1;

    $res = wget($ping_url, $params);
    if (!$res) {
        throw new RuntimeException("Couldn't read response from $ping_url");
    }

    list($headers, $data) = $res;
    // status line is first header in response
    $status = array_shift($headers);
    list($proto, $status, $msg) = explode(' ', trim($status), 3);
    if ($status != '200') {
        throw new RuntimeException("Could not ping the Eventum SCM handler script: HTTP status code: $status $msg");
    }

    $status = json_decode($data, true);
    // if response is json, try to figure error from there
    if (is_array($status)) {
        if ($status['code']) {
            throw new RuntimeException($status['message'], $status['code']);
        }
        $data = $status['message'];
    }

    // prefix response with our name
    foreach (explode("\n", trim($data)) as $line) {
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
        throw new RuntimeException("allow_url_fopen is disabled");
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