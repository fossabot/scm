#!/usr/bin/php
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2014-2016 Eventum Team.                                |
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

// URL to your Eventum installation.
// https is supported transparently by PHP 5 if you have openssl module enabled.
$eventum_url = 'http://eventum.example.com/';
// SCM repository name. Needed if multiple repositories configured

// default to $GL_REPO
$scm_name = getenv('GL_REPO') ?: 'git';

//
// DO NOT CHANGE ANYTHING AFTER THIS LINE
//

// save name of this script
$PROGRAM = basename(realpath(array_shift($argv)), '.php');

$dir = __DIR__;
require_once "$dir/helpers.php";

// load eventum-git-hook.conf.php from dir of this script if it exists
$configfile = "$dir/$PROGRAM.conf.php";
if (file_exists($configfile)) {
    require_once $configfile;
}

$nullsha1 = '0000000000000000000000000000000000000000';
$emptysha1 = '4b825dc642cb6eb9a060e54bf8d69288fbee4904';

$reflist = git_receive_refs();
// process each branch push
foreach ($reflist as $refs) {
    list($old, $new, $origin) = $refs;
    if ($new == $nullsha1) {
        // remote branch is deleted. nothing to do
        continue;
    }

    if ($old == $nullsha1) {
        // remote branch is created. use emptysha1 instead
        $old = $emptysha1;
    }

    $revlist = git_rev_list($old, $new, '--no-merges --author-date-order --reverse');
    foreach ($revlist as $rev) {
        git_scm_ping($old, $rev);
        $old = $rev;
    }
}

/**
 * Submit Git data to Eventum
 *
 * @param string $oldrev
 * @param string $rev
 */
function git_scm_ping($oldrev, $rev)
{
    $commit_msg = git_commit_msg($rev);
    $issues = match_issues($commit_msg);
    if (!$issues) {
        return;
    }

    global $PROGRAM, $scm_name;
    $username = git_commit_author($rev);
    $modified_files = git_commit_files($rev);
    $files = array();
    $old_versions = array();
    $new_versions = array();

    foreach ($modified_files as $i => $file) {
        $files[$i] = $file['filename'];

        $old_versions[$i] = $oldrev;
        $new_versions[$i] = $rev;
    }

    $params = array(
        'scm_name' => $scm_name,
        'username' => $username,
        'commit_msg' => $commit_msg,
        'issue' => $issues,
        'files' => $files,
        'commitid' => $rev,
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

/*
 * A post-receive hook gets its arguments from STDIN,
 * in the form <oldrev> <newrev> <refname>.
 * Since these arguments are coming from stdin, not from a command line argument,
 * you need to use read instead of $1 $2 $3.
 *
 * The post-receive hook can receive multiple branches at once (for example if someone does a git push --all),
 * so we also need to wrap the read in a while loop.
 */
function git_receive_refs()
{
    $result = array();
    $input = stream_get_contents(STDIN);
    foreach (explode(PHP_EOL, rtrim($input, PHP_EOL)) as $line) {
        $result[] = explode(' ', $line);
    }

    return $result;
}

/**
 * Get files affected from $rev
 *
 * @param string $rev
 * @return array
 */
function git_commit_files($rev)
{
    $files = execx("git show --pretty=format: --name-only $rev");
    $modified_files = array();
    foreach ($files as $filename) {
        $modified_files[] = array(
            'filename' => $filename,
            // parent not available. multiple parents when merging
            'old_revision' => null,
            'new_revision' => $rev,
        );
    }

    return $modified_files;
}

/**
 * @param string $old revision start
 * @param string $new revision end
 * @return array
 */
function git_rev_list($old, $new, $options = '')
{
    return execx("git rev-list $old..$new $options");
}

/**
 * @param string $rev
 * @return string
 */
function git_commit_author($rev)
{
    $output = execx("git log --format=%ae -n1 $rev");

    return current($output);
}

/**
 * @param string $rev
 * @return string
 */
function git_commit_msg($rev)
{
    $output = execx("git log --format=%B -n1 $rev");

    return current($output);
}
