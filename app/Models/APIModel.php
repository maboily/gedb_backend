<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Defines a model closely tied to the APIController.
 * Class APIModel
 * @package App\Models
 */
class APIModel extends Model
{
    public function __construct($resourceName = null) {
        parent::__construct();

        $this->resourceName = $resourceName;
    }

    /**
     * Query to use for selecting the data as a list.
     * @return mixed
     */
    public function selectAll()
    {
        return $this->select('*');
    }

    /**
     * Cache tags used for this model in the index method.
     * @param $resourceName
     * @return array
     */
    public static function selectAllCacheTags($resourceName)
    {
        return [$resourceName];
    }

    /**
     * Cache key used for this model in the index method.
     * @param $page
     * @param $perPage
     * @param $orderBy
     * @param $orderDirection
     * @return string
     */
    public static function selectAllCacheKey($resourceName, $page, $perPage, $orderBy, $orderDirection) {
        return "api.{$resourceName}.index[page={$page}&perPage={$perPage}&orderBy={$orderBy}&orderDirection={$orderDirection}]";
    }

    /**
     * Query to use for selecting a single entry.
     * @return mixed
     */
    public function selectOne()
    {
        return $this->select('*');
    }

    /**
     * Cache tags used for this model in the get method.
     * @param $resourceName
     * @return array
     */
    public static function selectOneCacheTags($resourceName)
    {
        return [$resourceName];
    }

    /**
     * Cache key used for this model in the get method.
     * @param $resourceName
     * @param $id
     * @return string
     */
    public static function selectOneCacheKey($resourceName, $id) {
        return "api.{$resourceName}.view[id={$id}]";
    }
}