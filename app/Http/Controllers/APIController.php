<?php


namespace App\Http\Controllers;

use App\Models\APIModel;
use Illuminate\Support\Facades\Input;
use Laravel\Lumen\Routing\Controller;
use App\Http\API\APIResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A resource controller implementation with defaults and authorized actions.
 * Class APIController
 * @package App\Http\Controllers
 */
class APIController extends Controller
{
    /** @var APIModel null */
    protected $modelInstance = null;

    /** @var APIResponse null */
    protected $response = null;

    /**
     * GET /{resource}/ action (all entries display)
     * @param $resourceName
     * @return mixed
     */
    public function index($resourceName)
    {
        // Authorization check
        $this->authorizeApiAccess('index');
        $this->verifyResource($resourceName);

        // Query parameters
        $perPage = Input::get('perPage') ?: app('config')->get('api.limits.pages')[0];
        $orderBy = Input::get('orderBy');
        $orderDirection = Input::get('orderDirection');
        $page = Input::get('page') ?: 1;

        // Validates query parameters
        $this->validatePagination($perPage);

        // Cache information
        $cacheTags = APIModel::selectAllCacheTags($resourceName);
        $cacheKey = APIModel::selectAllCacheKey($resourceName, $page, $perPage, $orderBy, $orderDirection);

        // Constructs API response to check for cache hit
        $this->responseFromCacheOrNew($cacheTags, $cacheKey);

        // Cache hit check
        if ($this->response->wasCached()) {
            return $this->response->get();
        } else {
            $this->instantiateResource($resourceName);

            // Paged result
            $result = $this->modelInstance->selectAll()->paginate($perPage)->toArray();

            // Returns result + cache save
            return $this->response->data($result['data'])
                ->header("orderBy", $orderBy)
                ->header("orderDirection", $orderDirection)
                ->header("page", $result['current_page'])
                ->header("perPage", $result['per_page'])
                ->header("total", $result['total'])
                ->get();
        }
    }

    /**
     * GET /{resource}/{id} action (single entry display)
     * @param $resourceName
     * @param $id
     * @return APIResponse|mixed|\Symfony\Component\HttpFoundation\Response
     */
    public function get($resourceName, $id)
    {
        // Authorization check
        $this->authorizeApiAccess('get');
        $this->verifyResource($resourceName);

        // Cache information
        $cacheTags = APIModel::selectOneCacheTags($resourceName);
        $cacheKey = APIModel::selectOneCacheKey($resourceName, $id);

        // Constructs API response to check for cache hit
        $this->responseFromCacheOrNew($cacheTags, $cacheKey);

        if ($this->response->wasCached()) {
            return $this->response->get();
        } else {
            $this->instantiateResource($resourceName);

            // Gets specific resource
            $result = $this->modelInstance->selectOne()->findOrFail($id);

            return $this->response->data($result)
                ->get();
        }
    }

    /**
     * POST /{resource}/ action (creates new single entry)
     */
    public function store()
    {
        // Authorization check
        $this->authorizeApiAccess('store');

        throw new \Exception("Not implemented");
    }

    /**
     * PUT or PATCH /{resource}/{id} action (updates single entry)
     */
    public function update()
    {
        // Authorization check
        $this->authorizeApiAccess('update');

        throw new \Exception("Not implemented");
    }

    /**
     * DELETE /{resource}/{id} action (deletes single entry)
     */
    public function destroy()
    {
        // Authorization check
        $this->authorizeApiAccess('destroy');

        throw new \Exception("Not implemented");
    }

    /**
     * Throws an exception if the client is not authorized to access a specific resource.
     * @param $permissionName Name of the permission to check.
     * @throws \Exception
     */
    protected function authorizeApiAccess($permissionName)
    {
        if (!in_array($permissionName, app('config')->get('api.authorized')))
            throw new AccessDeniedHttpException();
    }

    /**
     * Checks if the wanted pagination is a valid value.
     * @param $page
     */
    protected function validatePagination($page) {
        if (!in_array($page, app('config')->get('api.limits.pages')))
            throw new AccessDeniedHttpException('Invalid per-page parameter');
    }

    /**
     * Generates an API response instance from the given cache tags and cache key.
     * @param $cacheTags
     * @param $cacheKey
     */
    protected function responseFromCacheOrNew($cacheTags, $cacheKey)
    {
        $this->response = new APIResponse;
        $this->response->cache($cacheTags, $cacheKey);
    }

    /**
     * Verifies if the resource exists, throwing an exception if it doesn't.
     * @param $resourceName
     * @throws ResourceNotFoundException
     */
    protected function verifyResource($resourceName)
    {
        $resourceCheck = app('config')->get("api.routes.{$resourceName}", null);

        if ($resourceCheck === NULL)
            throw new NotFoundHttpException();
    }

    /**
     * Instantiates the model instance for the active resource.
     * @param $resourceName
     * @throws ResourceNotFoundException
     */
    protected function instantiateResource($resourceName)
    {
        $resourceCheck = app('config')->get("api.routes.{$resourceName}", null);
        $this->modelInstance = new $resourceCheck;
    }
}