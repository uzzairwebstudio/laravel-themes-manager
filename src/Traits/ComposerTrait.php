<?php

namespace Hexadog\ThemesManager\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Hexadog\ThemesManager\Helpers\Json;

trait ComposerTrait
{
	/**
	 * The package vendor.
	 *
	 * @var
	 */
	protected $vendor;

	/**
	 * The package name.
	 *
	 * @var
	 */
	protected $name;

	/**
	 * @var array of cached Json objects, keyed by filename
	 */
	protected $json = [];

	/**
	 * Get package class namespace
	 *
	 * @return string
	 */
	public function getNamespace()
	{
		$psr4_autoload = $this->get('autoload.psr-4');

		if (!is_null($psr4_autoload)) {
			return array_search('src', $psr4_autoload);
		}
		
		return "{$this->getStudlyVendor()}\\{$this->getStudlyName()}";
	}

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public function getVendor()
	{
		if (is_null($this->vendor) && ($this->vendor = $this->get('name'))) {
			$data = explode('/', $this->vendor);
			if (count($data) > 1) {
				$this->vendor = $data[0];
			} else {
				$this->vendor = null;
			}
		}

		return $this->vendor;
	}

	/**
	 * Get vendor in lower case.
	 *
	 * @return string
	 */
	public function getLowerVendor()
	{
		return mb_strtolower($this->getVendor());
	}

	/**
	 * Get vendor in studly case.
	 *
	 * @return string
	 */
	public function getStudlyVendor()
	{
		return Str::studly($this->getVendor());
	}

	/**
	 * Get namespace in snake case.
	 *
	 * @return string
	 */
	public function getSnakeVendor()
	{
		return Str::snake($this->getVendor());
	}

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public function getName()
	{
		if (is_null($this->name) && ($this->name = $this->get('name'))) {
			$data = explode('/', $this->name);
			if (count($data) > 1) {
				$this->name = $data[1];
			}
		}

		return $this->name;
	}

	/**
	 * Get name in lower case.
	 *
	 * @return string
	 */
	public function getLowerName()
	{
		return mb_strtolower($this->getName());
	}

	/**
	 * Get name in studly case.
	 *
	 * @return string
	 */
	public function getStudlyName()
	{
		return Str::studly($this->getName());
	}

	/**
	 * Get name in snake case.
	 *
	 * @return string
	 */
	public function getSnakeName()
	{
		return Str::snake($this->getName());
	}

	/**
	 * Get a specific data from json file by given the key.
	 *
	 * @param string $key
	 * @param null $default
	 *
	 * @return mixed
	 */
	public function get(string $key, $default = null)
	{
		return $this->json()->get($key, $default);
	}

	public function set(string $key, $value)
	{
		return $this->json()->set($key, $value)->save();
	}

	/**
	 * Handle call __toString.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getName();
	}

	/**
	 * Handle call to __get method.
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->get($key);
	}

	/**
	 * Handle call to __set method.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function __set($key, $value)
	{
		return $this->set($key, $value);
	}

	/**
	 * Get json contents from the cache, setting as needed.
	 *
	 * @param string $file
	 *
	 * @return Json
	 */
	private function json($file = null): Json
	{
		if ($file === null) {
			$file = 'composer.json';
		}

		return Arr::get($this->json, $file, function () use ($file) {
			return $this->json[$file] = new Json($this->getPath() . $file, app('files'));
		});
	}

	/**
	 * Scan for all available packages
	 *
	 * @throws Exception
	 */
	private function scan(string $path, string $class)
	{
		$content = collect();

		if ($path === null || empty($path)) {
			return $content;
		}

		$path = base_path($path);

		if ($this->files->exists($path)) {
			$foundComposers = array_filter($this->files->allFiles($path), function ($file) {
				return preg_match('/.*composer\.json$/U', $file->getFilename());
			});
			foreach ($foundComposers as $foundComposer) {
				$composerJson = new Json($foundComposer, app('files'));

				if ($composerJson->get('type') === 'laravel-theme') {
					$content->put($composerJson->get('name'), new $class(dirname($foundComposer)));
				}
			}
		}

		return $content;
	}
}
