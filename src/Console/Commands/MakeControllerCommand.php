<?php

namespace SdFramework\Console\Commands;

use SdFramework\Console\Command;
use SdFramework\Console\Input;
use SdFramework\Console\Output;

class MakeControllerCommand extends Command
{
    protected string $name = 'make:controller';
    protected string $description = 'Create a new controller class';

    protected function configure(): void
    {
        $this->addArgument('name', 'The name of the controller class');
        $this->addOption('resource', 'Create a resource controller with CRUD methods', 'r');
        $this->addOption('api', 'Create an API controller', 'a');
    }

    public function execute(Input $input, Output $output): int
    {
        $name = $input->getArgument(1);
        if (!$name) {
            $output->error('Controller name is required.');
            return 1;
        }

        // Ensure the name ends with Controller
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $isResource = $input->hasOption('resource');
        $isApi = $input->hasOption('api');

        try {
            $content = $this->generateController($name, $isResource, $isApi);
            $path = $this->getControllerPath($name);

            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }

            file_put_contents($path, $content);

            $output->success(sprintf('Controller [%s] created successfully.', $name));
            return 0;
        } catch (\Throwable $e) {
            $output->error($e->getMessage());
            return 1;
        }
    }

    private function generateController(string $name, bool $isResource, bool $isApi): string
    {
        $namespace = $this->getNamespace($name);
        $className = basename(str_replace('\\', '/', $name));

        $methods = $isResource ? $this->getResourceMethods($isApi) : $this->getBasicMethods($isApi);

        return <<<PHP
<?php

namespace App\\Controllers{$namespace};

use SdFramework\\Http\\Request;
use SdFramework\\Http\\Response;

class {$className}
{
{$methods}
}
PHP;
    }

    private function getResourceMethods(bool $isApi): string
    {
        if ($isApi) {
            return <<<'PHP'

    public function index(Request $request): Response
    {
        return Response::json(['message' => 'List of resources']);
    }

    public function show(Request $request, int $id): Response
    {
        return Response::json(['message' => 'Show resource ' . $id]);
    }

    public function store(Request $request): Response
    {
        return Response::json(['message' => 'Resource created'], 201);
    }

    public function update(Request $request, int $id): Response
    {
        return Response::json(['message' => 'Resource ' . $id . ' updated']);
    }

    public function destroy(Request $request, int $id): Response
    {
        return Response::json(['message' => 'Resource ' . $id . ' deleted']);
    }
PHP;
        }

        return <<<'PHP'

    public function index(Request $request): Response
    {
        return Response::html('index');
    }

    public function create(Request $request): Response
    {
        return Response::html('create');
    }

    public function store(Request $request): Response
    {
        // Handle the creation
        return Response::redirect('/resources');
    }

    public function show(Request $request, int $id): Response
    {
        return Response::html('show');
    }

    public function edit(Request $request, int $id): Response
    {
        return Response::html('edit');
    }

    public function update(Request $request, int $id): Response
    {
        // Handle the update
        return Response::redirect('/resources/' . $id);
    }

    public function destroy(Request $request, int $id): Response
    {
        // Handle the deletion
        return Response::redirect('/resources');
    }
PHP;
    }

    private function getBasicMethods(bool $isApi): string
    {
        if ($isApi) {
            return <<<'PHP'

    public function index(Request $request): Response
    {
        return Response::json(['message' => 'Hello from API']);
    }
PHP;
        }

        return <<<'PHP'

    public function index(Request $request): Response
    {
        return Response::html('index');
    }
PHP;
    }

    private function getControllerPath(string $name): string
    {
        $path = str_replace('\\', '/', $name);
        return getcwd() . '/app/Controllers/' . $path . '.php';
    }

    private function getNamespace(string $name): string
    {
        $parts = explode('\\', $name);
        array_pop($parts);
        return !empty($parts) ? '\\' . implode('\\', $parts) : '';
    }
}
