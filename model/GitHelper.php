<?php

namespace c00;


class GitHelper {

	/**
	 * Get the tag and commit hash of the current git state
	 *
	 * Runs 'git describe'
	 *
	 * @return string
	 */
	public static function gitVersion(): string
	{
		exec('git describe --always',$version);
		return $version[0];
	}

	public static function gitDateString(): string
	{
		exec('git log --pretty="%ci" -n1 HEAD', $date);
		return $date[0];
	}
}