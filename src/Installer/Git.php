<?php
/*
 * The MIT License
 *
 * Copyright 2016 Richard Jedlička <jedlicka.r@gmail.com> (http://uiii.cz)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace ProcessWireTestEnvs\Installer;


use ProcessWireTestEnvs\Helpers\Cmd;
use ProcessWireTestEnvs\Runner\Log;
use Webmozart\PathUtil\Path;

class Git {
	public static function cloneRepo($repoUrl, $repoPath) {
		Cmd::run("git clone", [$repoUrl, $repoPath]);
	}

	public static function checkout($repoPath, $target) {
		Cmd::run("git checkout -f", [$target], ['cwd' => $repoPath]);
	}

	public static function apply($repoPath, $patchPath) {
		Cmd::run("git apply", [$patchPath], ['cwd' => $repoPath]);
	}

	public static function getTags($repo) {
		return self::cacheTags(24 * 3600, $repo, function($repo) {
			$repoUrl = "https://github.com/$repo";
			$result = Cmd::run("git ls-remote --tags --refs $repoUrl");
			$tags = [];
			foreach (array_reverse($result->output) as $line) {
				list($sha, $ref) = explode("\t", $line);
				$tag = new \stdClass;
				$tag->name = explode('/', $ref)[2];
				$tag->sha = $sha;
				$tag->zip = $repoUrl . "/archive/$sha.zip";
				array_push($tags, $tag);
			}
			return $tags;
		});
	}

	/**
	 * @param int      $duration
	 * @param string   $repo
	 * @param callable $cachable
	 * @return mixed
	 */
	private static function cacheTags ($duration, $repo, callable $cachable)
	{
		$cacheFile = Path::join(__DIR__, 'cache/', str_replace('/', '_', $repo));
		$fresh = false;
		if (is_file($cacheFile)){
			$lastModified = filemtime($cacheFile);
			if (time() - $lastModified < $duration) {
				Log::debug("Cache hit for $repo");
				$fresh = true;
			}
		}

		if (!$fresh) {
			$tags = call_user_func($cachable, $repo);
			file_put_contents($cacheFile, serialize($tags));
		} else {
			$tags = unserialize(file_get_contents($cacheFile));
		}

		return $tags;
	}
}