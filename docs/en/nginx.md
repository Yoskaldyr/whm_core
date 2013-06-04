Example nginx locations config
==============================

All addons stored in folder `/addons/`

Location config for add-on's static files (js/css/images):

~~~
location ~ ^/addons/ {
	internal;
}

location ~ ^/(js|styles)/([a-z_]+)/([^/]+)$ {
	try_files /addons/$2/_Extras/$1/$2/$3 /addons/$2/upload/$1/$2/$3 $uri =404;
}
location ~ ^/(js|styles)/([a-z_]+)/([a-z_]+)/([^/]+)$ {
	try_files /addons/$2_$3/_Extras/$1/$2/$3/$4 /addons/$2_$3/upload/$1/$2/$3/$4 $uri =404;
}
~~~
