<?php

require_once('PackagerAbstractModel.php');
require_once('PackagerGit.php');

$git = new PackagerGit('D:\\xampp\\htdocs\\dbv');

$git->get_version_list();
$git->get_latest_version();
$git->output_version();
