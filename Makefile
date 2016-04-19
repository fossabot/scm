prefix := /usr
sbindir := $(prefix)/sbin
VCS := cvs svn git
TARGETS := $(patsubst %,eventum-%-hook.phar,$(VCS))

define find_tool
$(shell PATH=$$PATH:. which $1.phar 2>/dev/null || which $1 2>/dev/null || echo false)
endef

box := $(call find_tool, box)
php := php

all:
	@echo 'Run "make phar" to build standalone phar for: "$(VCS)"'

phar: $(TARGETS)

%.phar: %.php helpers.php Makefile box.json box.phar
# not possible to set options from commandline, so template this a bit
# https://github.com/box-project/box2/issues/91
	sed -e 's,@main@,$<,' -e 's,@output@,$@,' box.json > $@.json
	$(php) -d phar.readonly=0 $(box) build -v -c $@.json
	rm $@.json

box.phar:
	curl -LSs https://box-project.github.io/box2/installer.php | php

# install scm (cvs, svn, git) hooks
install:
	install -d $(DESTDIR)$(sbindir)
	for vcs in $(VCS); do \
		install -p eventum-$$vcs-hook.php $(DESTDIR)$(sbindir)/eventum-$$vcs-hook; \
	done
	cp -p helpers.php $(DESTDIR)$(sbindir)

clean:
	rm -vf $(TARGETS)
