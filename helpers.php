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
 * Submit SCM commit data to Eventum.
 *
 * @param array $params
 */
function scm_ping($params) {
    global $PROGRAM, $eventum_url;

    $ping_url = $eventum_url . "scm_ping.php";
    $res = wget($ping_url, $params);
    if (!$res) {
        error_log("Error: Couldn't read response from $ping_url");
        exit(1);
    }

    list($headers, $data) = $res;
    // status line is first header in response
    $status = array_shift($headers);
    list($proto, $status, $msg) = explode(' ', trim($status), 3);
    if ($status != '200') {
        error_log("Error: Could not ping the Eventum SCM handler script: HTTP status code: $status $msg");
        exit(1);
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
 */
function wget($url, $params, $headers = true)
{
    $url .= '?' . http_build_query($params, null, '&');

    // see if we can fopen
    $flag = ini_get('allow_url_fopen');
    if (!$flag) {
        error_log("ERROR: allow_url_fopen is disabled");
        return false;
    }

    // see if https is supported
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!in_array($scheme, stream_get_wrappers())) {
        error_log("ERROR: $scheme:// scheme not supported. Load openssl php extension?");
        return false;
    }

    ini_set('track_errors', 'On');
    $fp = fopen($url, 'r');
    if (!$fp) {
        error_log("ERROR: $php_errormsg");
        return false;
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