
Name: app-openfire
Epoch: 1
Version: 1.0.1
Release: 1%{dist}
Summary: Openfire
License: GPLv3
Group: ClearOS/Apps
Packager: eGloo
Vendor: Marc Laporte
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base

%description
Openfire is a real time collaboration (RTC) server licensed under the Open Source Apache License. It uses the only widely adopted open protocol for instant messaging, XMPP (also called Jabber). Openfire is incredibly easy to setup and administer, but offers rock-solid security and performance.

%package core
Summary: Openfire - Core
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: openfire

%description core
Openfire is a real time collaboration (RTC) server licensed under the Open Source Apache License. It uses the only widely adopted open protocol for instant messaging, XMPP (also called Jabber). Openfire is incredibly easy to setup and administer, but offers rock-solid security and performance.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/openfire
cp -r * %{buildroot}/usr/clearos/apps/openfire/

install -d -m 0755 %{buildroot}/var/clearos/openfire
install -d -m 0755 %{buildroot}/var/clearos/openfire/backup
install -D -m 0644 packaging/openfire.php %{buildroot}/var/clearos/base/daemon/openfire.php

%post
logger -p local6.notice -t installer 'app-openfire - installing'

%post core
logger -p local6.notice -t installer 'app-openfire-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/openfire/deploy/install ] && /usr/clearos/apps/openfire/deploy/install
fi

[ -x /usr/clearos/apps/openfire/deploy/upgrade ] && /usr/clearos/apps/openfire/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-openfire - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-openfire-core - uninstalling'
    [ -x /usr/clearos/apps/openfire/deploy/uninstall ] && /usr/clearos/apps/openfire/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/openfire/controllers
/usr/clearos/apps/openfire/htdocs
/usr/clearos/apps/openfire/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/openfire/packaging
%dir /usr/clearos/apps/openfire
%dir /var/clearos/openfire
%dir /var/clearos/openfire/backup
/usr/clearos/apps/openfire/deploy
/usr/clearos/apps/openfire/language
/usr/clearos/apps/openfire/libraries
/var/clearos/base/daemon/openfire.php
