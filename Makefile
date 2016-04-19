box := $(shell which box.phar 2>/dev/null || which box 2>/dev/null || echo false)
php := php
prefix := /usr
sbindir := $(prefix)/sbin
VCS := cvs svn git
TARGETS := $(patsubst %,eventum-%-hook.phar,$(VCS))

all:
	@echo 'Run "make phar" to build standalone phar for: "$(VCS)"'

phar: $(TARGETS)

%.phar: %.php helpers.php Makefile box.json
# not possible to set options from commandline, so template this a bit
# https://github.com/box-project/box2/issues/91
	sed -e 's,@main@,$<,' -e 's,@output@,$@,' box.json > $@.json
	$(php) -d phar.readonly=0 $(box) build -v -c $@.json
	rm $@.json

# install scm (cvs, svn, git) hooks
install:
	install -d $(DESTDIR)$(sbindir)
	for vcs in $(VCS); do \
		install -p eventum-$$vcs-hook.php $(DESTDIR)$(sbindir)/eventum-$$vcs-hook; \
	done
	cp -p helpers.php $(DESTDIR)$(sbindir)

clean:
	rm -vf *.phar
