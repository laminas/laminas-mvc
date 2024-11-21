<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Stdlib\ResponseInterface;
use Traversable;

use function array_merge;
use function is_array;

class RestfulTestController extends AbstractRestfulController
{
    public array $entities      = [];
    public array|object $entity = [];

    public ?ResponseInterface $headResponse = null;

    /**
     * Create a new resource
     *
     * @return mixed
     */
    public function create(mixed $data): array
    {
        return ['entity' => $data];
    }

    /**
     * Delete an existing resource
     *
     * @return mixed
     */
    public function delete(mixed $id): array
    {
        $this->entity = [];
        return [];
    }

    /**
     * Delete the collection
     *
     * @inheritDoc
     */
    public function deleteList($data): Response
    {
        if (is_array($this->entity)) {
            foreach ($data as $row) {
                foreach ($this->entity as $index => $entity) {
                    if ($row['id'] === $entity['id']) {
                        unset($this->entity[$index]);
                        break;
                    }
                }
            }
        }

        $response = $this->getResponse();
        $response->setStatusCode(204);
        $response->getHeaders()->addHeaderLine('X-Deleted', 'true');

        return $response;
    }

    /**
     * Return single resource
     *
     * @return mixed
     */
    public function get(mixed $id): array
    {
        return ['entity' => $this->entity];
    }

    /**
     * Return list of resources
     *
     * @return mixed
     */
    public function getList(): array
    {
        return ['entities' => $this->entities];
    }

    /**
     * Retrieve the headers for a given resource
     *
     * @inheritDoc
     */
    public function head($id = null): ?ResponseInterface
    {
        if ($id) {
            $this->getResponse()->getHeaders()->addHeaderLine('X-Laminas-Id', $id);
        }

        return $this->headResponse;
    }

    /**
     * Return list of allowed HTTP methods
     */
    public function options(): Response
    {
        $response = $this->getResponse();
        $headers  = $response->getHeaders();
        $headers->addHeaderLine('Allow', 'GET, POST, PUT, DELETE, PATCH, HEAD, TRACE');
        return $response;
    }

    /**
     * Patch (partial update) an entity
     *
     * @param  int $id
     * @param  array $data
     */
    public function patch($id, $data): array
    {
        $entity     = (array) $this->entity;
        $data['id'] = $id;
        $updated    = array_merge($entity, $data);
        return ['entity' => $updated];
    }

    /**
     * Replace the entire resource collection
     *
     * @param  array|Traversable $items
     */
    public function replaceList($items): iterable
    {
        return $items;
    }

    /**
     * Modify an entire resource collection
     *
     * @param  array|Traversable $items
     * @return array|Traversable
     */
    public function patchList($items): iterable
    {
        //This isn't great code to have in a test class, but I seems the simplest without BC breaks.
        if (
            isset($items['name'])
            && $items['name'] === 'testDispatchViaPatchWithoutIdentifierReturns405ResponseIfPatchListThrowsException'
        ) {
            parent::patchList($items);
        }
        return $items;
    }

    /**
     * Update an existing resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return mixed
     */
    public function update($id, $data): array
    {
        $data['id'] = $id;
        return ['entity' => $data];
    }

    public function editAction(): array
    {
        return ['content' => __FUNCTION__];
    }

    public function testSomeStrangelySeparatedWordsAction(): array
    {
        return ['content' => 'Test Some Strangely Separated Words'];
    }

    public function describe(): array
    {
        return ['description' => __METHOD__];
    }
}
