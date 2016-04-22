# Eventum SCM hook scripts

## CVS

Setup in your CVS server:

###  CVS 1.11:

 * `CVSROOT/loginfo`:

```
# process any message with eventum
ALL /path/to/eventum-cvs-hook.php $USER %{sVv}
```

###  CVS 1.12:

 * `CVSROOT/loginfo`:
```
# process any message with eventum
ALL /path/to/eventum-cvs-hook.php $USER "%p" %{sVv}
```
 * `CVSROOT/config`:
```
UseNewInfoFmtStrings=yes
```

## SVN

 * Setup in your svn server `hooks/post-commit`:

```sh
#!/bin/sh
REPO="$1"
REV="$2"
/path/toeventum-svn-hook.php "$REPO" "$REV"
```

## Git

 * Setup in your git repo `hooks/post-receive`:

```sh
#!/bin/sh
/path/toeventum-git-hook.php
```
