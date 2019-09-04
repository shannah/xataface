#!/bin/bash
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
DIST_DIR=$SCRIPTPATH/../dist
WWW_DIR=$SCRIPTPATH/../www
VERSION_FILE=$WWW_DIR/version.txt
[ -f "$VERSION_FILE" ] || echo "1.0 1" > $VERSION_FILE
VERSION=`php $SCRIPTPATH/print_config_var.php "XFShortVersionString"`
DBNAME=`php $SCRIPTPATH/print_config_var.php "_database.name"`
DIST_FILE=$DIST_DIR/$DBNAME-$VERSION.xfpkg
DIST_TMP=$DIST_DIR/$DBNAME-$VERSION
echo $DIST_FILE
[ -f $DIST_FILE ] && echo "$DIST_FILE already exists.  Increment version in www/version.txt file, or delete existing archive.\n" && exit 1
rm -rf "$DIST_TMP"
mkdir $DIST_DIR
mkdir $DIST_TMP
[ -f $WWW_DIR/templates_c ] && echo 'Signature: 8a477f597d28d172789f06886806bc55' > $WWW_DIR/templates_c/CACHEDIR.TAG
here=`pwd`
cd $SCRIPTPATH/..
tar -cf $DIST_TMP/www.tar  --exclude="templates_c" --exclude=".svn" --exclude=".git" --exclude=".gitignore" --exclude="xataface" www|| (echo "Failed to archive www directory." && exit 1)
cd $here
CONF=$DIST_TMP/conf.ini
touch $CONF

[ -f $WWW_DIR/conf.ini ] && cat $WWW_DIR/conf.ini >> $CONF
echo "\n" >> $CONF
[ -f $WWW_DIR/conf.ini.php ] && cat $WWW_DIR/conf.ini.php >> $CONF
echo "\n" >> $CONF
[ -f $WWW_DIR/conf.db.ini ] && cat $WWW_DIR/conf.db.ini >> $CONF
echo "\n" >> $CONF
[ -f $WWW_DIR/conf.db.ini.php ] && cat $WWW_DIR/conf.db.ini.php >> $CONF
status=`bash $SCRIPTPATH/mysql.server.sh status`
if [[ $status == *"ERROR!"* ]]; then
    $SCRIPTPATH/mysql.server.sh start || (echo "Failed to start mysql" && exit 1)
    function finish() {
        $SCRIPTPATH/mysql.server stop
    }
    trap finish EXIT
fi

sh $SCRIPTPATH/mysqldump.sh > $DIST_TMP/install.sql
mkdir $DIST_TMP/www
tar xf $DIST_TMP/www.tar -C $DIST_TMP
rm $DIST_TMP/www.tar