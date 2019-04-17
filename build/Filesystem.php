<?php
declare(strict_types=1);

namespace Firehed\Nf;

use FilesystemIterator;
use Generator;
use RuntimeException;

class Filesystem
{
    public function chmod(string $file, int $mode): void
    {
        if (chmod($file, $mode)) {
            return;
        }
        throw new RuntimeException('Failed to change file mode');
    }

    public function copy(string $from, string $to): void
    {
        if (is_dir($from)) {
            mkdir($to);
            foreach ($this->iterateOverDirectory($from) as $fromFile) {
                $target = $to . '/' . pathinfo($fromFile, \PATHINFO_BASENAME);
                $this->copy($fromFile, $target);
            }
            return;
        }
        if (copy($from, $to)) {
            return;
        }
        throw new RuntimeException("Could not copy form $from to $to");
    }

    /**
     * This treats the lack of the file to delete as success
     */
    public function delete(string $path, bool $recursive = false): void
    {
        if (!$this->exists($path)) {
            return;
        }
        if (is_dir($path)) {
            if ($recursive) {
                foreach ($this->iterateOverDirectory($path) as $file) {
                    $this->delete($file, true);
                }
            } else {
                throw new RuntimeException("$path is a directory, but recurisive not set");
            }
            if (rmdir($path)) {
                return;
            }
            throw new RuntimeException("Could not remove directory $path");
        }
        if (unlink($path)) {
            return;
        }
        throw new RuntimeException("Deletion of $path failed");
    }

    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function move(string $from, string $to): void
    {
        $this->copy($from, $to);
        $this->delete($from, true);
    }

    public function read(string $path): string
    {
        $data = file_get_contents($path);
        if ($data === false) {
            throw new RuntimeException("Could not read $path");
        }
        return $data;
    }

    private function iterateOverDirectory(string $path): Generator
    {
        $iterator = new FilesystemIterator(
            $path,
            FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
        );
        yield from $iterator;
    }
}
