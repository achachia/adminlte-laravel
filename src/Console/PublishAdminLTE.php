<?php

namespace Acacha\AdminLTETemplateLaravel\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use League\Flysystem\MountManager;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Adapter\Local as LocalAdapter;

/**
 * Class PublishAdminLTE.
 */
class PublishAdminLTE extends Command
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'adminlte-laravel:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Acacha AdminLTE Template files into laravel project';

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->publishHomeController();
        $this->changeRegisterController();
        $this->publishPublicAssets();
        $this->publishViews();
        $this->publishResourceAssets();
        $this->publishTests();
        $this->publishLanguages();
        $this->publishGravatar();
    }

    /**
     * Install Home Controller.
     */
    private function publishHomeController()
    {
        $this->install(\Acacha\AdminLTETemplateLaravel\Facades\AdminLTE::homeController());
    }

    /**
     * Install Auth controller.
     */
    private function changeRegisterController()
    {
        $this->install(\Acacha\AdminLTETemplateLaravel\Facades\AdminLTE::registerController());
    }

    /**
     * Install public assets.
     */
    private function publishPublicAssets()
    {
        $this->install(\Acacha\AdminLTETemplateLaravel\Facades\AdminLTE::publicAssets());
    }

    /**
     * Install views.
     */
    private function publishViews()
    {
        $this->install(\Acacha\AdminLTETemplateLaravel\Facades\AdminLTE::views());
    }

    /**
     * Install resource assets.
     */
    private function publishResourceAssets()
    {
        $this->install(\Acacha\AdminLTETemplateLaravel\Facades\AdminLTE::resourceAssets());
    }

    /**
     * Install resource assets.
     */
    private function publishTests()
    {
        $this->install(\Acacha\AdminLTETemplateLaravel\Facades\AdminLTE::tests());
    }

    /**
     * Install language assets.
     */
    private function publishLanguages()
    {
        $this->install(\Acacha\AdminLTETemplateLaravel\Facades\AdminLTE::languages());
    }

    /**
     * Install gravatar config file.
     */
    private function publishGravatar()
    {
        $this->install(\Acacha\AdminLTETemplateLaravel\Facades\AdminLTE::gravatar());
    }

    /**
     * Install files from array.
     *
     * @param $files
     */
    private function install($files)
    {
        foreach ($files as $fileSrc => $fileDst) {
            if (file_exists($fileDst) && !$this->confirmOverwrite(basename($fileDst))) {
                return;
            }
            if ($this->files->isFile($fileSrc)) {
                $this->publishFile($fileSrc, $fileDst);
            } elseif ($this->files->isDirectory($fileSrc)) {
                $this->publishDirectory($fileSrc, $fileSrc);
            } else {
                $this->error("Can't locate path: <{$fileSrc}>");
            }
            copy($fileSrc, $fileDst);
            $this->info('Copied file ' . $fileSrc . ' to ' . $fileDst );
        }
    }

    /**
     * @param $fileName
     * @param string $prompt
     *
     * @return bool
     */
    protected function confirmOverwrite($fileName, $prompt = '')
    {
        $prompt = (empty($prompt))
            ? $fileName.' already exists. Do you want to overwrite it? [y|N]'
            : $prompt;
        return $this->confirm($prompt, false);
    }

    /**
     * Create the directory to house the published files if needed.
     *
     * @param  string  $directory
     * @return void
     */
    protected function createParentDirectory($directory)
    {
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Publish the file to the given path.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishFile($from, $to)
    {
        if ($this->files->exists($to) && ! $this->option('force')) {
            return;
        }
        $this->createParentDirectory(dirname($to));
        $this->files->copy($from, $to);
        $this->status($from, $to, 'File');
    }

    /**
     * Publish the directory to the given directory.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishDirectory($from, $to)
    {
        $manager = new MountManager([
            'from' => new Flysystem(new LocalAdapter($from)),
            'to' => new Flysystem(new LocalAdapter($to)),
        ]);
        foreach ($manager->listContents('from://', true) as $file) {
            if ($file['type'] === 'file' && (! $manager->has('to://'.$file['path']) || $this->option('force'))) {
                $manager->put('to://'.$file['path'], $manager->read('from://'.$file['path']));
            }
        }
        $this->status($from, $to, 'Directory');
    }

    /**
     * Write a status message to the console.
     *
     * @param  string  $from
     * @param  string  $to
     * @param  string  $type
     * @return void
     */
    protected function status($from, $to, $type)
    {
        $from = str_replace(base_path(), '', realpath($from));
        $to = str_replace(base_path(), '', realpath($to));
        $this->line('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
    }
}