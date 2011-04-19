#!/usr/bin/env php
<?php
require_once(__DIR__ . '/bootstrap.php');

foreach(Place::open_all() as $place) {
	$place->generate_markers();
}