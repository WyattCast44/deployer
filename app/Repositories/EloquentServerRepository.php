<?php

namespace App\Repositories;

use App\Repositories\Contracts\ServerRepositoryInterface;
use App\Repositories\EloquentRepository;
use App\Jobs\TestServerConnection;
use App\Server;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * The server repository.
 */
class EloquentServerRepository extends EloquentRepository implements ServerRepositoryInterface
{
    use DispatchesJobs;

    /**
     * Class constructor.
     *
     * @param  Server                   $model
     * @return EloquentServerRepository
     */
    public function __construct(Server $model)
    {
        $this->model = $model;
    }

    /**
     * Gets all servers.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->model
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Creates a new instance of the server.
     *
     * @param  array $fields
     * @return Model
     */
    public function create(array $fields)
    {
        // Get the current highest server order
        $max = Server::where('project_id', $fields['project_id'])
                     ->orderBy('order', 'DESC')
                     ->first();

        $order = 0;
        if (isset($max)) {
            $order = $max->order + 1;
        }

        $fields['order'] = $order;

        return $this->model->create($fields);
    }

    /**
     * Updates a server instance by it's ID and queues it for testing
     *
     * @param  array $fields
     * @param  int   $model_id
     * @return Model
     */
    public function queueForTesting($server_id)
    {
        $server = $this->getById($server_id);

        if (!$server->isTesting()) {
            $server->status = Server::TESTING;
            $server->save();

            $this->dispatch(new TestServerConnection($server));
        }
    }
}
