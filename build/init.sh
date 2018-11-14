#!/bin/sh -x

#create git user if not exist
if ! id git > /dev/null 2>&1 ; then
    useradd -g www-data -m -s /bin/sh git
fi

#make .ssh directory for git user if not exist
if ! [ -d /home/git/.ssh ] ; then
    mkdir -m 700 /home/git/.ssh
fi

#create ssh authorized keys and chown it to git with strict ssh perms
umask 077
touch /home/git/.ssh/authorized_keys
chown -R git /home/git/.ssh
umask 0

#fix php doesn't create run directory for fcgi server socket
if ! [ -d /run/php ] ; then
    mkdir /run/php
fi

#fix some other ubuntu related shit
#gitphp   | /run/sshd must be owned by root and not group or world-writable.
if ! [ -d /run/sshd ] ; then
    mkdir -m 700 /run/sshd
fi
