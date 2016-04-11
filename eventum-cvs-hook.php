#!/usr/bin/php
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

/*
 * Setup in your CVS server:
 *
 * for CVS 1.11:
 * CVSROOT/loginfo:
 * # process any message with eventum
 * ALL /path/to/eventum-cvs-hook.php $USER %{sVv}
 *
 * for CVS 1.12:
 * CVSROOT/loginfo:
 * # process any message with eventum
 * ALL /path/to/eventum-cvs-hook.php $USER "%p" %{sVv}
 *
 * CVSROOT/config:
 * UseNewInfoFmtStrings=yes
 */

// URL to your Eventum installation.
// https is supported transparently by PHP 5 if you have openssl module enabled.
$eventum_url = 'http://eventum.example.com/';
// SCM repository name. Needed if multiple repositories configured
$scm_name = 'cvs';

//
// DO NOT CHANGE ANYTHING AFTER THIS LINE
//

// save name of this script
$PROGRAM = basename(realpath(array_shift($argv)), '.php');

$dir = __DIR__;
require_once "$dir/helpers.php";

// load eventum-cvs-hook.conf.php from dir of this script if it exists
$configfile = "$dir/$PROGRAM.conf.php";
if (file_exists($configfile)) {
    require_once $configfile;
}

$commit_msg = cvs_commit_msg();

// parse the commit message and get all issue numbers we can find
$issues = match_issues($commit_msg);

if ($issues) {
    // grab fileinfo
    list($username, $modified_files) = cvs_commit_info($argv);

    $files = array();
    $old_versions = array();
    $new_versions = array();
    $commitid = array();
    for ($i = 0; $i < count($modified_files); $i++) {
        $files[$i] = $modified_files[$i]['filename'];
        $commitid[$i] = $modified_files[$i]['commitid'];
        $old_versions[$i] = $modified_files[$i]['old_revision'];
        $new_versions[$i] = $modified_files[$i]['new_revision'];
    }

    $commitid = array_unique($commitid);
    if (count($commitid) > 1) {
        throw new InvalidArgumentException('Commit Id should be unique');
    }
    $commitid = current($commitid);

    $params = array(
        'scm_name' => $scm_name,
        'username' => $username,
        'commit_msg' => $commit_msg,
        'issue' => $issues,
        'files' => $files,
        'commitid' => $commitid,
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
 * @param array $argv
 * @return array of username, modified files
 */
function cvs_commit_info($argv)
{
    // user who is committing these changes
    $username = array_shift($argv);

    if (count($argv) == 1) {
        $modified_files = cvs_parse_info_1_11($argv);
    } else {
        $modified_files = cvs_parse_info_1_12($argv);
    }

    // parse told to skip (for example adding new dir)
    if (!$modified_files) {
        exit(0);
    }

    return array($username, $modified_files);
}

/**
 * assume the old way ("PATH {FILE,rev1,rev2 }+")
 * CVSROOT/loginfo: ALL eventum-cvs-hook $USER %{sVv}
 *
 * @return array
 */
function cvs_parse_info_1_11($argv)
{
    $args = explode(' ', array_shift($argv));

    // save what the name of the module is
    $cvs_module = array_shift($args);

    // skip if we're importing or adding new dirrectory
    $msg = implode(' ', array_slice($args, -3));
    if (in_array($msg, array('- Imported sources', '- New directory'))) {
        return null;
    }

    // now parse the list of modified files
    $modified_files = array();
    foreach ($args as $file_info) {
        list($filename, $old_revision, $new_revision) = explode(',', $file_info);
        $modified_files[] = array(
            'filename' => "$cvs_module/$filename",
            'old_revision' => cvs_filter_none($old_revision),
            'new_revision' => cvs_filter_none($new_revision),
        );
    }

    return $modified_files;
}

/**
 * assume the new way ("PATH" {"FILE" "rev1" "rev2"}+)
 * CVSROOT/loginfo: ALL eventum-cvs-hook $USER "%p" %{sVv}
 *
 * @return array
 */
function cvs_parse_info_1_12($args)
{
    // save what the name of the module is
    $cvs_module = array_shift($args);

    // skip if we're importing or adding new directory
    // TODO: checked old way with CVS 1.11, but not checked the new way
    $msg = implode(' ', array_slice($args, -3));
    if (in_array($msg, array('- Imported sources', '- New directory'))) {
        return null;
    }

    // now parse the list of modified files
    $modified_files = array();
    while ($file_info = array_splice($args, 0, 3)) {
        list($filename, $old_revision, $new_revision) = $file_info;
        $modified_files[] = array(
            'filename' => "$cvs_module/$filename",
            'old_revision' => cvs_filter_none($old_revision),
            'new_revision' => cvs_filter_none($new_revision),
            'commitid' => cvs_commitid($filename),
        );
    }

    return $modified_files;
}

/**
 * Extract 'commitid' from file, Requires CVS 1.12+
 *
 * @param string $filename
 * @return string
 */
function cvs_commitid($filename)
{
    $result = execx('cvs -Qn status ' . escapeshellarg($filename));

    $pattern = '/Commit Identifier:\s+(?P<commitid>\S+)/';
    // find line matching 'Commit Identifier'
    $lines = preg_grep($pattern, $result);
    if (!$lines) {
        return null;
    }
    // match commit id
    if (!preg_match($pattern, current($lines), $m)) {
        return null;
    }

    return $m['commitid'];
}

/**
 * filter out NONE revision
 *
 * @param string $rev
 * @return null
 */
function cvs_filter_none($rev)
{
    if ($rev != 'NONE') {
        return $rev;
    }

    return null;
}

/**
 * Obtain CVS commit message
 *
 * @return string
 */
function cvs_commit_msg()
{
    // get the full commit message
    $input = stream_get_contents(STDIN);
    $commit_msg = rtrim(substr($input, strpos($input, 'Log Message:') + strlen('Log Message:') + 1));

    return $commit_msg;
}
