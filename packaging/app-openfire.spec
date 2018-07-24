
Name: app-openfire
Epoch: 1
Version: 1.2.11
Release: 1%{dist}
Summary: Openfire
License: GPLv3
Group: Applications/Apps
Packager: eGloo
Vendor: WikiSuite
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base
Requires: app-certificate-manager

%description
Openfire is a real time collaboration (RTC) server licensed under the Open Source Apache License. It uses the only widely adopted open protocol for instant messaging, XMPP (also called Jabber). Openfire is incredibly easy to setup and administer, but offers rock-solid security and performance.

%package core
Summary: Openfire - API
License: LGPLv3
Group: Applications/API
Requires: app-base-core
Requires: app-base >= 1:2.4.15
Requires: app-certificate-manager-core >= 1:2.4.16
Requires: app-users-core >= 1:2.4.0
Requires: app-groups-core
Requires: app-ldap-core
Requires: app-network-core >= 1:2.4.3
Requires: app-openfire-plugin-core
Requires: app-system-database-core >= 1:2.3.3
Requires: openfire >= 4.2.0
Requires: openssl

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
install -d -m 0755 %{buildroot}/var/clearos/openfire/focus-user
install -D -m 0755 packaging/lets-encrypt-event %{buildroot}/var/clearos/events/lets_encrypt/openfire
install -D -m 0644 packaging/openfire.php %{buildroot}/var/clearos/base/daemon/openfire.php
install -D -m 0755 packaging/openldap-configuration-event %{buildroot}/var/clearos/events/openldap_configuration/openfire
install -D -m 0755 packaging/openldap-online-event %{buildroot}/var/clearos/events/openldap_online/openfire

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
%exclude /usr/clearos/apps/openfire/unify.json
%dir /usr/clearos/apps/openfire
%dir /var/clearos/openfire
%dir /var/clearos/openfire/backup
%dir /var/clearos/openfire/focus-user
/usr/clearos/apps/openfire/deploy
/usr/clearos/apps/openfire/language
/usr/clearos/apps/openfire/libraries
/var/clearos/events/lets_encrypt/openfire
/var/clearos/base/daemon/openfire.php
/var/clearos/events/openldap_configuration/openfire
/var/clearos/events/openldap_online/openfire
