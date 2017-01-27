#!/bin/sh

MYSQL="/usr/clearos/sandbox/usr/bin/mysql"

APP_DB_CONFIG="/var/clearos/system_database/openfire"
APP_DB_NAME="openfire"
APP_DB_USERNAME="openfire"

# Grab database password
#-----------------------

APP_DB_PASSWORD=`grep ^password $APP_DB_CONFIG 2>/dev/null | sed "s/^password[[:space:]]*=[[:space:]]*//"`

if [ -z "$APP_DB_PASSWORD" ]; then
    echo "Openfire database password not found"
    exit 1
fi

# setProperty function
#---------------------

setProperty() {
    PROPERTY=$1
    VALUE=$2

    echo "Setting property - $PROPERTY: $VALUE"
    $MYSQL -u"$APP_DB_USERNAME" -p"$APP_DB_PASSWORD" -e"INSERT INTO ofProperty (name,propValue) VALUES ('$PROPERTY','$VALUE') ON DUPLICATE KEY UPDATE name = VALUES(name), propValue = VALUES(propValue);" $APP_DB_NAME
}
