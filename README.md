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

## Git

