<?php

namespace Studio\Totem\Http\Controllers;

use File;
use function storage_path;
use Studio\Totem\Contracts\TaskInterface;

class ExportTasksController extends Controller
{
    /**
     * @var TaskInterface
     */
    private $tasks;

    /**
     * ExportTasksController constructor.
     * @param TaskInterface $tasks
     */
    public function __construct(TaskInterface $tasks)
    {
        parent::__construct();

        $this->tasks = $tasks;
    }

    /**
     * Export all tasks to a json file.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function index()
    {
        File::put(config('totem.storage_path').DIRECTORY_SEPARATOR.'tasks.json', $this->tasks->findAll()->toJson());

        return response()
            ->download(config('totem.storage_path').DIRECTORY_SEPARATOR.'tasks.json', 'tasks.json')
            ->deleteFileAfterSend(true);
    }
}
