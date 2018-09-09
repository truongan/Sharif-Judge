#!/bin/bash

usage(){
cat << EOF
usage: $0 [-i install_dir] [-o public_dir] -u db_user -p db_password [-n site_name] [base_url]

base url will be set in config.php

OPTIONS:
	-h show this message
	-i install directory, default to current working directory
	-o public directory to put index.php, default to be the public
	   directory in the same directory as install directory
	-u database username
	-p database password
	-d database name
	-n site name to display on top bar and construct base url for uit classes
	base_url: Will be use to set base url for the site when site_name is not specified. The https:// part must be included
EOF
}

install="`pwd`"
public=''
db_user=''
db=''
db_password=''
base_url=''
site_name=''
while getopts "hi:o:u:p:d:n:" ops ; do
	case "${ops}" in
		h)	usage ;;
		i)	install=${OPTARG};;
		o)	public=${OPTARG};;
		u)	db_user=${OPTARG};;
		p)	db_password=${OPTARG};;
		d)	db=${OPTARG};;
		n)  site_name=${OPTARG};;
		*)	usage; exit 1;;
	esac
done
shift $((OPTIND-1))

base_url=$1
lw_site_name=`echo $site_name | tr '[:upper:]' '[:lower:]'`

if [ "$site_name" = "" ]; then
	if [ "$base_url" = "" ]; then
		usage; exit 1
	fi
	site_name="Wecode-Judge"
else
	if [ "$base_url" = "" ] ; then
		base_url="https://khmt.uit.edu.vn/laptrinh/`echo $site_name | tr '[:upper:]' '[:lower:]'`/"
	fi
fi

if [ "$db_user" = "" ]; then
	usage; exit 1
fi
echo $public
if [ "$public" = "" ]; then
	public="$install/../public_html"
fi

if [ "$db" = '' ]; then
	db="$db_user"
fi

#convert install and public to absolute path
if [ -d "$install" ]; then
	install=`readlink -f $install`
else
	echo "Installation directory: $install not found"
fi
if [ -d "$public" ]; then
	public=`readlink -f $public`
else
	echo "Public directory: $public not found"
fi

cat << EOF
"install=$install"
"public=$public"
"db_user=$db_user"
"db=$db"
"db_password=$db_password"
"base_url=$base_url"
"site_name=$site_name"
EOF

cd $install
git clone  'https://github.com/truongan/wecode-judge' .
git checkout working-updateci


cd $public
if cmp -s $install $public; then
	echo "Installation dir and public dir are the same; that may pose security risk"
else
	read -p "$public/index.php $public/assets $public/.htaccess will be delete permenantly. Continue?" -n 1 -r REPLY
	echo
	if [[ $REPLY =~ ^[Yy]$ ]]
	then
	  	rm index.html index.php .htacess
	  	rm -rf ./assets
		ln -s $install/index.php $install/assets $install/.htaccess $install/.user.ini .
	else
		echo "Abort"
		exit 0
	fi
fi
echo sed -i "s@system_path = 'system'@system_path = '$install/system'@g" index.php
sed -i "s@system_path = 'system'@system_path = '$install/system'@g" index.php
sed -i "s@application_folder = 'application'@application_folder = '$install/application'@g" index.php

cd $install/application/config
cp config.php.example config.php
cp database.php.example database.php
mkdir $install/application/session/

echo sed -i "s@base_url'] = ''@base_url'] = '$base_url'@g" config.php
sed -i "s@base_url'] = ''@base_url'] = '$base_url'@g" config.php
sed -i "s@index_page'] = 'index.php'@index_page'] = ''@g" config.php
sed -i "s@sess_save_path'] = NULL@sess_save_path'] = '$install/application/session/'@g" config.php

#UIT Related settings
#sed -i "s@cookie_path']		= '/'@cookie_path']		= '/laptrinh/$lw_site_name/'@g" config.php
#cp /opt/Login.php $install/application/controllers/Login.php



pwd
sed -i "s/homestead/$db_user/g" database.php
sed -i "s/secret/$db_password/g" database.php
echo sed -i "s/sharif/$db/g" database.php
sed -i "s/sharif/$db/g" database.php

cd $install/application/controllers
echo sed -i "s@_sitenametobereplace_@$site_name@g" Install.php
sed -i "s@_sitenametobereplace_@$site_name@g" Install.php

#Add default admin user, very dangerous and should not be enable by default
#php $install/index.php  abc abc%40def.com random_string false
