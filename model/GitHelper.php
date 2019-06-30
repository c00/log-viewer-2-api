<?php

namespace c00;


class GitHelper {

	/**
	 * Get the tag and commit hash of the current git state
	 *
	 * Runs 'git describe'
	 *
	 * @return string|null
	 */
	public static function gitVersion()
	{
		exec('git describe --always',$version);
		return $version[0];
	}

	public static function gitDateString()
	{
		exec('git log --pretty="%ci" -n1 HEAD', $date);
		return $date[0];
	}
}