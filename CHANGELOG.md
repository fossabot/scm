Eventum SCM hook scripts
========================

2016-??-??, Version [3.1.0]
----------------------------

This version of hooks require Eventum 3.1.0.
From this version onwards eventum-scm is not released as part of main Eventum release.

- cvs/svn/git hooks rewritten to handle new payload for Eventum 3.1.0
- use JSON payload to post data to Eventum, [134fd35]
- lose config, use command line arguments to configure hooks
- use GL_REPO as git project name

2016-04-19, Version [3.0.12]
----------------------------

- cvs/svn/git: include commitid information on commits

2015-10-31, Version [3.0.4]
---------------------------

- no functional changes

2015-10-13, Version [3.0.3]
---------------------------

- git: default to `GL_REPO`, [6a5d58c]

2015-08-04, Version [3.0.2]
---------------------------

- fix cvs hook handling in old (1.11) cvs server, [2a83268]
- add makefile and box configuration to build standalone .phar [7927586]

2014-11-30, Version [3.0.0]
---------------------------

- moved out of Eventum sourcetree as separate git repo
- added git hook script [0e6c1ea], [LP#1078454]
- use json format to read scm ping responses
- restructure code to be more modular, move common code to helpers
- eventum now supports multiple scm repositories [d9f120b]

2014-10-04, Version [2.4.0]
---------------------------

- no functional changes

2014-01-28, Version [2.3.6]
---------------------------

- no changes

2014-01-24, Version [2.3.5]
---------------------------

- no changes

2013-11-28, Version [2.3.4]
---------------------------

- cvs: handle warnings better when `cvs add directory` is performed [44c9d4a]
- load local config for CVS/SVN integration from script dir [371973b]

2012-05-30, Version [2.3.3]
---------------------------

- cvs: support CVS 1.11 and 1.12 formats (autodetected if configured correctly) [62b7f9c]

2011-12-03, Version [2.3.2]
---------------------------

- use PHP's native `stream_get_contents()` instead of getInput function to read data from STDIN [2589912]

2011-02-10, Version [2.3.1]
---------------------------

- no changes

2010-07-28, Version [2.3]
-------------------------

- cvs: handle importing sources and adding new directories nicely [930e438]
- cvs: commit hook prints remote response [a83f486]

2009-01-14, Version [2.2]
-------------------------

- no functional changes

2008-01-09, Version [2.1.1]
---------------------------

- cvs: handle errors better from HTTP response [8e10cfe]

2007-11-20, Version [2.1]
-------------------------

- no functional changes

2007-04-17, Version [2.0.1]
---------------------------

- no functional changes

2007-04-12, Version [2.0.0]
---------------------------

- fix issue id detection in scm commit message [a567490]
- new config format, use `$eventum_url` variable, allows `https://` urls [b6bf3b1]
- drop use legacy `$HTTP_SERVER_VARS`
- initial svn hook script [698176a]
- allow SCM commit messages contain multiple issue IDs [e89814b]
- properly encode post data containing `+` (use `rawurlencode` instead of `base64_encode`) [ac8b3ee]
- report errors from from Eventum server [60304fb]

[3.1.0]: https://github.com/eventum/scm/compare/v3.0.12...master
[3.0.12]: https://github.com/eventum/scm/compare/v3.0.4...v3.0.12
[3.0.4]: https://github.com/eventum/scm/compare/v3.0.3...v3.0.4
[3.0.3]: https://github.com/eventum/scm/compare/v3.0.2...v3.0.3
[3.0.2]: https://github.com/eventum/scm/compare/v3.0.0-pre1...v3.0.2
[3.0.0]: https://github.com/eventum/scm/compare/v2.4.0-pre1...v3.0.0-pre1
[2.4.0]: https://github.com/eventum/scm/compare/v2.3.6...v2.4.0-pre1
[2.3.6]: https://github.com/eventum/scm/compare/v2.3.5...v2.3.6
[2.3.5]: https://github.com/eventum/scm/compare/v2.3.4...v2.3.5
[2.3.4]: https://github.com/eventum/scm/compare/v2.3.3...v2.3.4
[2.3.3]: https://github.com/eventum/scm/compare/v2.3.2...v2.3.3
[2.3.2]: https://github.com/eventum/scm/compare/v2.3.1...v2.3.2
[2.3.1]: https://github.com/eventum/scm/compare/v2.3...v2.3.1
  [2.3]: https://github.com/eventum/scm/compare/v2.2...v2.3
  [2.2]: https://github.com/eventum/scm/compare/v2.1.1...v2.2
[2.1.1]: https://github.com/eventum/scm/compare/v2.1...v2.1.1
  [2.1]: https://github.com/eventum/scm/compare/v2.0.1...v2.1
[2.0.1]: https://github.com/eventum/scm/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/eventum/scm/compare/v1.7.1...v2.0.0
[LP#1078454]: https://bugs.launchpad.net/eventum/+bug/1078454
[0e6c1ea]: https://github.com/eventum/scm/commit/0e6c1ea
[2a83268]: https://github.com/eventum/scm/commit/2a83268
[6a5d58c]: https://github.com/eventum/scm/commit/6a5d58c
[d9f120b]: https://github.com/eventum/scm/commit/d9f120b
[44c9d4a]: https://github.com/eventum/scm/commit/44c9d4a
[371973b]: https://github.com/eventum/scm/commit/371973b
[62b7f9c]: https://github.com/eventum/scm/commit/62b7f9c
[2589912]: https://github.com/eventum/scm/commit/2589912
[930e438]: https://github.com/eventum/scm/commit/930e438
[a83f486]: https://github.com/eventum/scm/commit/a83f486
[8e10cfe]: https://github.com/eventum/scm/commit/8e10cfe
[a567490]: https://github.com/eventum/scm/commit/a567490
[698176a]: https://github.com/eventum/scm/commit/698176a
[b6bf3b1]: https://github.com/eventum/scm/commit/b6bf3b1
[e89814b]: https://github.com/eventum/scm/commit/e89814b
[ac8b3ee]: https://github.com/eventum/scm/commit/ac8b3ee
[60304fb]: https://github.com/eventum/scm/commit/60304fb
[7927586]: https://github.com/eventum/scm/commit/7927586
[134fd35]: https://github.com/eventum/scm/commit/134fd35
