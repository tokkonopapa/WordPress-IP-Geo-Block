#! /bin/bash
# A modification of Dean Clatworthy's deploy script at: https://github.com/deanc/wordpress-plugin-git-svn
# The difference is that this script lives in the plugin's git repo & doesn't require an existing SVN repo.

# structure on http://plugins.svn.wordpress.org/ip-geo-block/
# /ip-geo-block
#	assets/
#		icon-128x128.png
#		screenshot-1.png
#		screenshot-2.png
#		screenshot-3.png
#		screenshot-4.png
#		screenshot-5.png
#	branches/
#	tags/
#		1.0.0/
#		...
#		2.0.0/
#			LICENSE.txt
#			README.txt
#			admin/
#			classes/
#			database/
#			includes/
#			index.php
#			ip-geo-block.php
#			languages/
#			samples.php
#			uninstall.php
#	trunk/
#		LICENSE.txt
#		README.txt
#		admin/
#		classes/
#		database/
#		includes/
#		index.php
#		ip-geo-block.php
#		languages/
#		samples.php
#		uninstall.php

# main config
PLUGINSLUG="ip-geo-block"
CURRENTDIR=`pwd`
MAINFILE="$PLUGINSLUG.php" # this should be the name of your main php file in the wordpress plugin

# git config
GITPATH="$CURRENTDIR/$PLUGINSLUG/" # this file should be in the base of your git repository

# svn config
SVNPATH="/tmp/$PLUGINSLUG" # path to a temp SVN repo. No trailing slash required and don't add trunk.
SVNURL="http://plugins.svn.wordpress.org/$PLUGINSLUG/" # Remote SVN repo on wordpress.org, with no trailing slash
SVNUSER="tokkonopapa" # your svn username


# Let's begin...
echo ".........................................."
echo 
echo "Preparing to deploy wordpress plugin"
echo 
echo ".........................................."
echo 

# Check version in readme.txt is the same as plugin file
NEWVERSION1=`grep "^Stable tag" $GITPATH/readme.txt | awk '{print $NF}'`
echo "readme version: $NEWVERSION1"
NEWVERSION2=`grep "^Version" $GITPATH/$MAINFILE | awk '{print $NF}'`
echo "$MAINFILE version: $NEWVERSION2"

#if [ "$NEWVERSION1" != "$NEWVERSION2" ]; then echo "Versions don't match. Exiting...."; exit 1; fi

echo "Versions match in readme.txt and PHP file. Let's proceed..."

cd $GITPATH
echo -e "Enter a commit message for this new version: \c"
read COMMITMSG
git commit -am "$COMMITMSG"

echo "Tagging new version in git"
git tag -a "$NEWVERSION1" -m "Tagging version $NEWVERSION1"

echo "Pushing latest commit to origin, with tags"
git push origin master
git push origin master --tags

echo 
echo "Creating local copy of SVN repo ..."
svn co $SVNURL $SVNPATH

echo "Exporting the HEAD of master from git to the trunk of SVN"
git checkout-index -a -f --prefix=$SVNPATH/trunk/

echo "Ignoring github specific files and deployment script"
svn propset svn:ignore "deploy.sh
README.md
Thumbs.db
.git
.gitignore" "$SVNPATH/trunk/"

echo "Changing directory to SVN and committing to trunk"
cd $SVNPATH/trunk/

# re-construct PLUGINSLUG dir
echo "Setting trunc"
cp -Rp $PLUGINSLUG/* ./
rm -rf $PLUGINSLUG

# Support for the /assets folder on the .org repo.
echo "Moving assets"
rm -f $SVNPATH/assets/*
mv -f assets/* $SVNPATH/assets/
rmdir assets

# Update all the files that are not set to be ignored
svn status | grep -v "^.[ \t]*\..*" | grep "^\!" | awk '{print $2}' | xargs svn del
svn status | grep -v "^.[ \t]*\..*" | grep "^?"  | awk '{print $2}' | xargs svn add
svn commit --username=$SVNUSER -m "$COMMITMSG"

echo "Creating new SVN tag & committing it"
cd $SVNPATH

# Delete unused files
#svn delete --force \
#	trunk/classes/class-ip-geo-block-api.php \
#	trunk/includes/upgrade.php \
#	trunk/admin/js/footable.all.min.js \
#	trunk/admin/js/auth-nonce.js \
#	trunc/includes/Net/PEAR.php
svn delete --force \
	trunk/includes/venders/

# Copy all files to tags
svn copy trunk/ tags/$NEWVERSION1/
cd $SVNPATH/tags/$NEWVERSION1
svn commit --username=$SVNUSER -m "Tagging version $NEWVERSION1"

# for assets
echo "Commit assets"
cd $SVNPATH/assets/
svn status | grep -v "^.[ \t]*\..*" | grep "^\!" | awk '{print $2}' | xargs svn del
svn status | grep -v "^.[ \t]*\..*" | grep "^?"  | awk '{print $2}' | xargs svn add
svn commit --username=$SVNUSER -m "$NEWVERSION1"

echo "Removing temporary directory $SVNPATH"
rm -fr $SVNPATH/

echo "*** FIN ***"
