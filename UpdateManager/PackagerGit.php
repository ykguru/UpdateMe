<?php

class PackagerGit extends PackagerAbstractModel
{

	private $GIT_DIR = '';
	private $GIT_BRANCH = '';

	public function __construct($git_dir, $git_branch = 'master')
	{
		$this->GIT_DIR = $git_dir;
		$this->GIT_BRANCH = $git_branch;
	}

	public function get_latest_version()
	{
		//$a = $this->git('git log --first-parent --pretty=format:%H --branches=remotes/origin/master');
		$a = $this->git_commit_count();
		print_r($a);
	}

	public function get_version_list()
	{

	}

	public function output_version($version2 = false, $version1 = false)
	{

	}

	private function git_commit_count($branch = false)
	{
		if ($branch==false) $branch = $this->GIT_BRANCH;
		$count = $this->git('git rev-list --count --first-parent ' . $branch);
		if (is_array($count) && isset($count[0]))
			return $count[0];
		else
			return false;
	}

	private function git_commit_list($branch = false)
	{
		if ($branch==false) $branch = $this->GIT_BRANCH;
		return $this->git('git log --first-parent --pretty=format:%H --branches=' . $branch);
	}

	private function git_branch()
	{
		$exec = $this->git('git branch -a');
		foreach ($exec as &$v) {
			$v = substr($v, 2);
		}
		return $exec;
	}

	private function git($git_command)
	{
		$cur_dir = getcwd();
		chdir($this->GIT_DIR);
		$result = false;
		exec($git_command, $result);
		chdir($cur_dir);
		return $result;
	}

}