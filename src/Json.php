<?php

declare(strict_types=1);

/*
 * This file is part of https://github.com/josantonius/php-json repository.
 *
 * (c) Josantonius <hello@josantonius.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Josantonius\Json;

use Josantonius\Json\Exception\GetFileException;
use Josantonius\Json\Exception\JsonErrorException;
use Josantonius\Json\Exception\CreateFileException;
use Josantonius\Json\Exception\CreateDirectoryException;
use Josantonius\Json\Exception\UnavailableMethodException;

/**
 * PHP simple library for managing JSON files.
 */
class Json
{
    /**
     * If the file path is a URL.
     */
    private bool $isUrl;

    /**
     * @throws CreateDirectoryException
     * @throws CreateFileException
     * @throws JsonErrorException
     */
    public function __construct(private string $filepath)
    {
        $this->isUrl = filter_var($filepath, FILTER_VALIDATE_URL) !== false;

        if (!$this->isUrl) {
            $this->createFileIfNotExists($filepath);
        }
    }

    /**
     * Get the content of the JSON file or a remote JSON file.
     *
     * @throws GetFileException
     * @throws JsonErrorException
     */
    public function get(): array
    {
        return $this->getFileContents();
    }

    /**
     * Set the content of the JSON file.
     *
     * @throws CreateFileException
     * @throws JsonErrorException
     * @throws UnavailableMethodException
     */
    public function set(array|object $content): void
    {
        $this->isUrl ? $this->throwUnavailableMethodException() : $this->saveToJsonFile($content);
    }

    /**
     * Merge into JSON file.
     *
     * @throws CreateFileException
     * @throws GetFileException
     * @throws JsonErrorException
     * @throws UnavailableMethodException
     */
    public function merge(array|object $content): array
    {
        $content = array_merge($this->getFileContents(), (array) $content);

        $this->isUrl ? $this->throwUnavailableMethodException() : $this->saveToJsonFile($content);

        return $content;
    }

    /**
     * Push on the JSON file.
     *
     * @throws CreateFileException
     * @throws GetFileException
     * @throws JsonErrorException
     * @throws UnavailableMethodException
     */
    public function push(array|object $content): array
    {
        $data = $this->getFileContents();

        array_push($data, $content);

        $this->isUrl ? $this->throwUnavailableMethodException() : $this->saveToJsonFile($data);

        return $data;
    }

    /**
     * Create file if not exists.
     *
     * @throws CreateDirectoryException
     * @throws CreateFileException
     * @throws JsonErrorException
     */
    private function createFileIfNotExists(): void
    {
        if (!file_exists($this->filepath)) {
            $this->createDirIfNotExists();
            $this->saveToJsonFile([]);
        }
    }

    /**
     * Create directory if not exists.
     *
     * @throws CreateDirectoryException
     */
    private function createDirIfNotExists(): void
    {
        $path = dirname($this->filepath) . DIRECTORY_SEPARATOR;

        if (!is_dir($path) && !@mkdir($path, 0777, true)) {
            throw new CreateDirectoryException($path);
        }
    }

    /**
     * Get the content of the JSON file or a remote JSON file.
     *
     * @throws GetFileException
     * @throws JsonErrorException
     */
    private function getFileContents(): array
    {
        $json = @file_get_contents($this->filepath);

        if ($json === false) {
            throw new GetFileException($this->filepath);
        }

        $array = json_decode($json, true);

        $this->checkJsonLastError();

        return $array;
    }

    /**
     * Save content in JSON file.
     *
     * @throws CreateFileException
     * @throws JsonErrorException
     */
    private function saveToJsonFile(array|object $array): void
    {
        $json = json_encode($array, JSON_PRETTY_PRINT);

        $this->checkJsonLastError();

        if (@file_put_contents($this->filepath, $json) === false) {
            throw new CreateFileException($this->filepath);
        }
    }

    /**
     * Check for JSON errors.
     *
     * @throws JsonErrorException
     */
    private function checkJsonLastError(): void
    {
        if (json_last_error()) {
            throw new JsonErrorException();
        }
    }

    /**
     * Throw exception if the method is not available for remote JSON files.
     *
     * @throws UnavailableMethodException
     */
    private function throwUnavailableMethodException(): void
    {
        $method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        throw new UnavailableMethodException($method);
    }
}
