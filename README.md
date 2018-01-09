# Eventum SCM hook scripts
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Feventum%2Fscm.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Feventum%2Fscm?ref=badge_shield)


## CVS

Setup in your CVS server:

###  CVS 1.11:

 * `CVSROOT/loginfo`:

```
# process any message with eventum
ALL /path/to/eventum-cvs-hook.php -n cvs http://eventum.example.org/ $USER %{sVv}
```

###  CVS 1.12:

 * `CVSROOT/loginfo`:
```
# process any message with eventum
ALL /path/to/eventum-cvs-hook.php -n cvs http://eventum.example.org/ $USER "%p" %{sVv}
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
/path/to/eventum-svn-hook.php -n svn http://eventum.example.org/ "$REPO" "$REV"
```

## Git

 * Setup in your git repo `hooks/post-receive`:

```sh
#!/bin/sh
/path/to/eventum-git-hook.php -n git http://eventum.example.org/
```


## License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Feventum%2Fscm.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2Feventum%2Fscm?ref=badge_large)