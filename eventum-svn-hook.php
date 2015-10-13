#!/usr/bin/php
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// |          Adam Ratcliffe <adam.ratcliffe@geosmart.co.nz>              |
// |          Frederik M. Kraus <f.kraus@pangora.com>                     |
// |          Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * @see http://forge.mysql.com/wiki/Eventum:Subversion_integration about SVN integration.
 *
 * Setup in your svn server hooks/post-commit:
 *
 * #!/bin/sh
 * REPO="$1"
 * REV="$2"
 * /path/toeventum-svn-hook.php "$REPO" "$REV"
 */

// URL to your Eventum installation.
// https is supported transparently by PHP 5 if you have openssl module enabled.
$eventum_url = 'http://eventum.example.com/';
// SCM repository name. Needed if multiple repositories configured
$scm_name = 'svn';

//
// DO NOT CHANGE ANYTHING AFTER THIS LINE
//

// save name of this script
$PROGRAM = basename(realpath(array_shift($argv)), '.php');

$dir = __DIR__;
require_once "$dir/helpers.php";

// load eventum-svn-hook.conf.php from dir of this script if it exists
$configfile = "$dir/$PROGRAM.conf.php";
if (file_exists($configfile)) {
    require_once $configfile;
}

if ($argc < 3) {
    error_log("$PROGRAM: Missing arguments, got " . ($argc - 1) . ', expected 2');
    exit(1);
}

$repos = $argv[0];
$new_revision = $argv[1];
$old_revision = $new_revision - 1;

if (!isset($svnlook)) {
    $svnlook = '/usr/bin/svnlook';
}

if (!is_executable($svnlook)) {
    error_log("$PROGRAM: svnlook is not executable, edit \$svnlook");
    exit(1);
}

$results = svnlook('info', $repos, $new_revision);
list($username, $date, $commit_msg) = svn_commit_info($results);

// parse the commit message and get all issue numbers we can find
$issues = match_issues($commit_msg);
if ($issues) {
    $module = array();
    $files = array();
    $old_versions = array();
    $new_versions = array();

    $modified_files = svn_commit_files($repos, $old_revision, $new_revision);
    foreach ($modified_files as $i => &$file) {
        list($scm_module, $filename) = fileparts($file['filename']);

        $module[$i] = $scm_module;
        $files[$i] = $filename;

        // add old revision if content was changed
        if (array_search('A', $file['flags']) === false) {
            $old_versions[$i] = $file['old_revision'];
        }
        // add new revision if it was not removed
        if (array_search('D', $file['flags']) === false) {
            $new_versions[$i] = $file['new_revision'];
        }
    }

    $params = array(
        'scm_name' => $scm_name,
        'username' => $username,
        'commit_msg' => $commit_msg,
        'issue' => $issues,
        'module' => $module,
        'files' => $files,
        'old_versions' => $old_versions,
        'new_versions' => $new_versions,
    );

    try {
        scm_ping($params);
    } catch (Exception $e) {
        error_log("ERROR[$PROGRAM]: " . $e->getMessage());
        exit(1);
    }
}

/**
 * Process username, date and commit message from svnlook output
 *
 * @param array $results
 * @return array
 */
function svn_commit_info($results)
{
    // get commit date and username and commit message
    $username = array_shift($results);
    $date = array_shift($results);

    // ignore commit message length value
    array_shift($results);

    // get the full commit message
    $commit_msg = implode("\n", $results);

    return array($username, $date, $commit_msg);
}

/**
 * Get files affected from $rev
 *
 * @param string $rev
 * @return array
 */
function svn_commit_files($repo, $old_revision, $new_revision)
{
    $modified_files = array();

    $files = svnlook('changed', $repo, $new_revision);
    foreach ($files as $file_info) {
        // http://svnbook.red-bean.com/en/1.7/svn.ref.svnlook.c.changed.html
        // flags:
        // - 'A ' Item added to repository
        // - 'D ' Item deleted from repository
        // - 'U ' File contents changed
        // - '_U' Properties of item changed; note the leading underscore
        // - 'UU' File contents and properties changed
        list($flags, $filename) = preg_split('/\s+/', $file_info, 2);
        $modified_files[] = array(
            'flags' => preg_split('//', $flags, -1, PREG_SPLIT_NO_EMPTY),
            'filename' => $filename,
            'old_revision' => $old_revision,
            'new_revision' => $new_revision
        );
    }

    return $modified_files;
}

/**
 * Execute svnlook command on $repo for $revision
 *
 * @param string $command
 * @param string $repo
 * @param int $revision
 * @return array
 */
function svnlook($command, $repo, $revision)
{
    global $svnlook;

    return execx("$svnlook $command $repo -r $revision");
}
