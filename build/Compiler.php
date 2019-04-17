<?php
declare(strict_types=1);

namespace Firehed\Nf;

use Phar;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class Compiler
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function compile(string $commandClass): void
    {
        $outfile = 'nfc.phar';
        if (file_exists($outfile)) {
            $this->logger->info('Removing existing file');
            unlink($outfile);
        }
        $phar = new Phar($outfile, 0, 'nf.phar');
        $phar->startBuffering();
        // Stub first to get initial validation done
        $phar->setStub($this->getStub($commandClass));

        $root = dirname(__DIR__);
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->in("$root/src");
        foreach ($finder as $file) {
            $this->addFile($phar, $file->getPathname());
        }

        $this->addVendor($phar);

        $phar->stopBuffering();
        unset($phar); // force output
    }

    private function addFile(Phar $phar, string $path): void
    {
        if (!file_exists($path)) {
            throw new \Exception("$path not found");
        }
        $this->logger->debug("Adding file $path");
        $rp = $this->getRelativePath($path);
        $code = file_get_contents($path);
        $phar->addFromString($rp, $code);
    }

    private function addDirectory(Phar $phar, string $dir): void
    {
        $this->logger->info("Adding directory $dir");
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->exclude('Tests')
            ->exclude('tests')
            ->exclude('docs')
            ->in(dirname(__DIR__) . '/' . $dir);
        foreach ($finder as $file) {
            $this->addFile($phar, $file->getPathname());
        }
    }

    private function addVendor(Phar $phar): void
    {
        $lockJson = file_get_contents('composer.lock');
        $data = json_decode($lockJson, true, JSON_THROW_ON_ERROR);
        assert(isset($data['packages']));
        foreach ($data['packages'] as $package) {
            $this->addVendorPackage($phar, $package);
        }

        // Add composer stuff for autoloading
        $backupDir = $this->backupComposer();
        $this->addDirectory($phar, 'vendor/composer');
        $this->addFile($phar, realpath('vendor/autoload.php'));
        $this->restoreComposer($backupDir);
    }

    private function backupComposer(): string
    {
        $fs = new Filesystem();
        $root = dirname(__DIR__);
        $tmp = sys_get_temp_dir() . '/' . bin2hex(random_bytes(8));
        $this->logger->debug("Backing up composer data to $tmp");
        $fs->mirror("$root/vendor/composer", $tmp);
        $command = implode(' ', [
            'composer',
            'dumpautoload',
            '--no-dev',
            '--optimize',
            "--working-dir $root",
        ]);
        $this->logger->debug("Rebuilding composer without dev data ($command)");
        shell_exec($command);
        return $tmp;
    }

    private function restoreComposer(string $from): void
    {
        $this->logger->debug("Restoring composer data from $from");
        $root = dirname(__DIR__);
        $fs = new Filesystem();
        $fs->remove("$root/vendor/composer");
        $fs->rename($from, "$root/vendor/composer");
    }

    private function addVendorPackage(Phar $phar, array $packageData): void
    {
        $name = $packageData['name'];
        $this->addDirectory($phar, "vendor/$name");
    }

    private function getStub(string $commandClass): string
    {
        if (!class_exists($commandClass)) {
            throw new \Exception("Class $commandClass does not exist");
        }
        if (!is_subclass_of($commandClass, Command::class)) {
            throw new \Exception("$commandClass is not a Command");
        }
        $rc = new ReflectionClass($commandClass);

        // check constructor and instanceof Command?
        $stub = <<<PHP
        #!/usr/bin/env php
        <?php
        require 'vendor/autoload.php';
        use Symfony\Component\Console\Application;

        \$command = new $commandClass();

        \$application = new Application();
        \$application->add(\$command);
        \$application->setDefaultCommand(\$command->getName());
        \$application->run();
        __HALT_COMPILER();
        PHP;

        $this->logger->debug("Generated stub for $commandClass");
        $this->logger->debug($stub);

        return $stub;
    }

    private function getRelativePath(string $absolute): string
    {
        $root = dirname(__DIR__);
        if (0 !== strpos($absolute, $root)) {
            throw new \Exception('Absolute does not start with root');
        }
        return substr($absolute, strlen($root) + 1);
    }
}