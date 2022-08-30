<?php
require __DIR__ . '/../vendor/autoload.php';
$cacher_dir = __DIR__ . '/css/';
/** @var  Ikiu\GoogleFontsCache\Bootstrap $ikcache */
$ikcache = new Ikiu\GoogleFontsCache\Bootstrap($cacher_dir);
$ikcache->init();
$ikcache->run();
