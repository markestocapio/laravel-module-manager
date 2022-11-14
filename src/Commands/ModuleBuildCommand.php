<?php

namespace Nasirkhan\ModuleManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Str;

class ModuleBuildCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'module:build {moduleName : The name of the module to be built} {--force : Repace the existing module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $force = $this->option('force');

        $config = config("module-manager");

        // Module name | Single word | Ucfirst
        $moduleName = Str::ucfirst(Str::singular(Str::studly($this->argument('moduleName'))));
        // $moduleNamePlural = Str::plural($moduleName);
        // $moduleNameLower = Str::lower($moduleName);

        // $this->info("moduleName: " . $moduleName);

        $this->generate($moduleName, $force);

        // print_r($config);

        // echo "\n\n TestCommand \n\n";

        echo "\n\n";
    }

    public function generate($moduleName, $force) 
    {
        $this->info("Generating module: " . $moduleName);

        $config = config("module-manager");

        $namespace = $config["namespace"];
        $moduleNamePlural = Str::plural($moduleName);
        $moduleNameLower = Str::lower($moduleName);
        $moduleNameLowerPlural = Str::plural($moduleNameLower);

        $composerVendor = $config['composer']['vendor'];
        $composerAuthor = $config['composer']['author']['name'];
        $composerAuthorEmail = $config['composer']['author']['email'];
        $this->line("$composerVendor | $composerAuthor | $composerAuthorEmail");

        $search = ['{{moduleName}}', '{{moduleNamePlural}}', '{{moduleNameLower}}', '{{moduleNameLowerPlural}}', '{{namespace}}', '{{composerVendor}}', '{{composerAuthor}}', '{{composerAuthorEmail}}'];
        $replace = [$moduleName, $moduleNamePlural, $moduleNameLower, $moduleNameLowerPlural, $namespace, $composerVendor, $composerAuthor, $composerAuthorEmail];


        $basePath = $namespace . '\\' . $moduleName;
        // $this->line("basePath: " . $basePath);

        if (File::isDirectory($basePath)) {
            if ($force) {
                $this->warn("Module already exists. Replacing...\n");
                File::deleteDirectory($basePath);
                File::makeDirectory($basePath);
                $this->info("'$basePath' directory created");
                $this->createFiles($moduleName, $basePath, $search, $replace, $force);
            } else {
                $this->error("Module '$moduleName' already exists. Use --force to replace.");
                return;
            }
        } else {
            $this->info("'$basePath' directory does not exist");
            File::makeDirectory($basePath);
            $this->info("'$basePath' directory created");

            $this->createFiles($moduleName, $basePath, $search, $replace, $force);            
        }
    }

    public function createFiles($moduleName, $basePath, $search, $replace, $force) 
    {
        $moduleNamePlural = Str::plural($moduleName);
        $moduleNameLower = Str::lower($moduleName);
        $moduleNameLowerPlural = Str::plural($moduleNameLower);

        $config = config("module-manager");

        $stubs_path = $config['stubs']['path'];

        $files_list = $config['module']['files'];

        foreach ($files_list as $file => $file_path) {
            $this->newLine();

            $this->line("Source | $file > $stubs_path/" . $file_path[0]);

            $content_stub = File::get("$stubs_path/" . $file_path[0]);
            $content = str_replace($search, $replace, $content_stub);

            $this->line("$file > $basePath/" . $file_path[1]);

            $destination_value = $this->setFilePath($file, $file_path[1], $moduleName);
            $this->warn("$destination_value");

            $destination = "$basePath/" . $this->setFilePath($file, $file_path[1], $moduleName);            
            $this->warn("$destination");

            $pathToFile = $destination_value;
            $this->warn("$pathToFile");

            if (count(explode('/', $pathToFile)) > 1) {
                $fileName = basename($pathToFile);

                $folders = explode('/', str_replace('/' . $fileName, '', $pathToFile));

                $currentFolder = "$basePath/";
                foreach ($folders as $folder) {
                    $currentFolder .= $folder . DIRECTORY_SEPARATOR;
                    
                    if (!File::isDirectory($currentFolder)) {
                        File::makeDirectory($currentFolder);
                    }
                }
            }
            
            if (File::exists($destination)) {
                if ($force) {
                    File::put($destination, $content);
                    $this->info("'$destination' file replaced");
                } 
                else 
                {
                    $this->error("'$destination' file already exists");
                }
            } 
            else 
            {
                File::put($destination, $content);
                $this->info("'$destination' file created");
            }
        }
    }

    public function setFilePath($filetype, $filePath, $moduleName)
    {
        $value = "";
        $moduleNamePlural = Str::plural($moduleName);
        $moduleNameLower = Str::lower($moduleName);
        $moduleNameLowerPlural = Str::plural($moduleNameLower);

        switch ($filetype) 
        {
            case 'command':
                $value = $moduleName . "Command.php";
                $filePath = str_replace("StubCommand.php", $value, $filePath);
                break;

            case 'database':
                $value = date('Y_m_d_his_') . "create_". $moduleNameLowerPlural ."_table.php";
                $filePath = str_replace("stubMigration.php", $value, $filePath);
                break;

            case 'factories':
                $value = $moduleName ."Factory.php";
                $filePath = str_replace("stubFactory.php", $value, $filePath);
                break;

            case 'seeders':
                $value = $moduleName . "DatabaseSeeder.php";
                $filePath = str_replace("stubSeeders.php", $value, $filePath);
                break;

            case 'models':
                $value = $moduleName . ".php";
                $filePath = str_replace("stubModel.php", $value, $filePath);
                break;

            case 'providers':
                $value = $moduleName . "ServiceProvider.php";
                $filePath = str_replace("stubServiceProvider.php", $value, $filePath);
                break;

            case 'controller_backend':
                $value = $moduleNamePlural . "Controller.php";
                $filePath = str_replace("stubBackendController.php", $value, $filePath);
                break;

            case 'controller_frontend':
                $value = $moduleNamePlural . "Controller.php";
                $filePath = str_replace("stubFrontendController.php", $value, $filePath);
                break;

            case 'views_backend_index':
                $value = $moduleNameLowerPlural;
                $filePath = str_replace("stubViews", $value, $filePath);
                break;

            case 'views_backend_index_datatable':
                $value = $moduleNameLowerPlural;
                $filePath = str_replace("stubViews", $value, $filePath);
                break;

            case 'views_backend_create':
                $value = $moduleNameLowerPlural;
                $filePath = str_replace("stubViews", $value, $filePath);
                break;

            case 'views_backend_form':
                $value = $moduleNameLowerPlural;
                $filePath = str_replace("stubViews", $value, $filePath);
                break;

            case 'views_backend_show':
                $value = $moduleNameLowerPlural;
                $filePath = str_replace("stubViews", $value, $filePath);
                break;

            case 'views_backend_edit':
                $value = $moduleNameLowerPlural;
                $filePath = str_replace("stubViews", $value, $filePath);
                break;

            case 'views_backend_trash':
                $value = $moduleNameLowerPlural;
                $filePath = str_replace("stubViews", $value, $filePath);
                break;

            case 'views_frontend_index':
                $value = $moduleNameLowerPlural;
                $filePath = str_replace("stubViews", $value, $filePath);
                break;

            case 'views_frontend_show':
                $value = $moduleNameLowerPlural;
                $filePath = str_replace("stubViews", $value, $filePath);
                break;
            
            default:
                # code...
                break;
        }
        
        return $filePath;
    }
}
