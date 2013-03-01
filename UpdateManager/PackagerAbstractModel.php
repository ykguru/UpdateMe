<?php

abstract class PackagerAbstractModel
{

	abstract public function get_latest_version();

	abstract public function get_version_list();

	abstract public function output_version($version2 = false, $version1 = false);

}