<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'dashboard';
$route['404_override'] = 'errors/error_404';
$route['translate_uri_dashes'] = FALSE;
$route['unauthorized_403'] = 'errors/error_403';

$route['login'] = 'auth';
$route['auth/login'] = 'auth/login';
$route['auth/logout'] = 'auth/logout';

$route['pegawai'] = 'pegawai';
$route['pegawai/fetchData'] = 'pegawai/fetchData';
$route['pegawai/add'] = 'pegawai/add';
$route['pegawai/getKab'] = 'pegawai/getKab';
$route['pagawai/getKec'] = 'pagawai/getKec';
$route['pagawai/getKel'] = 'pagawai/getKel';
$route['pagawai/simpanData'] = 'pagawai/simpanData';
$route['pagawai/delete'] = 'pagawai/delete';
$route['pagawai/edit/(:any)'] = 'pagawai/edit/$1';
$route['pagawai/updateData'] = 'pagawai/updateData';
$route['pagawai/detail'] = 'pagawai/detail';
$route['pegawai/cari_pegawai'] = 'Pegawai/cari_pegawai';

$route['nasabah'] = 'nasabah';
$route['nasabah/fetchData'] = 'nasabah/fetchData';
$route['nasabah/add'] = 'nasabah/add';
$route['nasabah/detail'] = 'nasabah/detail';
$route['nasabah/delete'] = 'nasabah/delete';
$route['nasabah/generate_norek'] = 'nasabah/generate_norek';



$route['users'] = 'users';
$route['users/fetchData'] = 'users/fetchData';
$route['users/add'] = 'users/add';
$route['users/simpanData'] = 'users/simpanData';
$route['users/delete'] = 'users/delete';
$route['users/edit/(:any)'] = 'users/edit/$1';

$route['jenis_tabungan'] = 'kategori';
$route['jenis_tabungan/fetchData'] = 'kategori/fetchData';
$route['jenis_tabungan/add'] = 'kategori/add';
$route['jenis_tabungan/simpanData'] = 'kategori/simpanData';
$route['jenis_tabungan/delete'] = 'kategori/delete';
$route['jenis_tabungan/edit/(:any)'] = 'kategori/edit/$1';
$route['jenis_tabungan/updateData'] = 'kategori/updateData';
$route['jenis_tabungan/detail/(:any)'] = 'kategori/detail/$1';