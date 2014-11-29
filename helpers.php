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
 * Fetch $url, return response and optionally unparsed headers array.
 *
 * @author Elan Ruusamäe <glen@delfi.ee>
 * @param string $url
 * @param boolean $headers = false
 * @return mixed
 */
function wget($url, $headers = false)
{
    // see if we can fopen
    $flag = ini_get('allow_url_fopen');
    if (!$flag) {
        fwrite(STDERR, "ERROR: allow_url_fopen is disabled\n");

        return false;
    }

    // see if https is supported
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!in_array($scheme, stream_get_wrappers())) {
        fwrite(STDERR, "ERROR: $scheme:// scheme not supported. Load openssl php extension?\n");

        return false;
    }

    ini_set('track_errors', 'On');
    $fp = fopen($url, 'r');
    if (!$fp) {
        fwrite(STDERR, "ERROR: $php_errormsg\n");

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
    } else {
        return $data;
    }
}