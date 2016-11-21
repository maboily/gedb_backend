<?php


namespace App\Http\API;

use Illuminate\Support\Facades\Cache;

// API structure:
// - headers: what would normally be in HTTP headers, but is there for reliability (proxies). This includes list parameters. Optional.
// - debug: debugging information, such as cache hit status. Only visible in local environment.
// - data: content of the request. Required.

/**
 * Fluent class for API responses which supports caching and a basic default structure.
 * Class APIResponse
 * @package App\Http\API
 */
class APIResponse {
    protected $headers = [];
    protected $debug = [];
    protected $data;

    protected $cacheTags;
    protected $cacheKey;
    protected $wasCached = false;
    protected $mustCache = false;
    protected $cachedData = [];

    /**
     * Specifies the end-result will be cached, and checks if there is an already-existing cached entry.
     * @param $tags Cache store tags.
     * @param $key Cache store key.
     */
    public function cache($tags, $key) {
        $this->mustCache = true;
        $this->cacheTags = $tags;
        $this->cacheKey = $key;

        if (Cache::tags($this->cacheTags)->has($this->cacheKey)) {
            $this->wasCached = true;
            $this->cachedData = Cache::tags($this->cacheTags)->get($this->cacheKey);
        } else {
            $this->wasCached = false;
        }

        $this->debug('wasCached', $this->wasCached());
        $this->debug('cacheTags', $this->cacheTags);
        $this->debug('cacheKey', $this->cacheKey);
    }

    /**
     * Adds a non-HTTP header to the request.
     * @param $key
     * @param $value
     * @return APIResponse $this
     */
    public function header($key, $value) {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Adds debug information to the response only if the environment is local.
     * @param $key
     * @param $value
     * @return APIResponse $this
     */
    public function debug($key, $value) {
        if (env('APP_DEBUG', false) == true) {
            $this->debug[$key] = $value;
        }

        return $this;
    }

    /**
     * Inserts the requested data into the response.
     * @param $data
     * @return APIResponse $this
     */
    public function data($data) {
        $this->data = $data;

        return $this;
    }

    /**
     * Wraps the final response, caches it if necessary and sends its result to the caller.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function get() {
        // Saves cached data
        if (!$this->wasCached()) {
            // Forms response
            $fullData = [];
            if (count($this->headers) != 0) $fullData['headers'] = $this->headers;
            if (count($this->debug) != 0) $fullData['debug'] = $this->debug;
            $fullData['data'] = $this->data;

            // Caches data if requested
            if ($this->mustCache) Cache::tags($this->cacheTags)->forever($this->cacheKey, $fullData);

            return response()->json($fullData);
        } else {
            if (count($this->debug) != 0) $this->cachedData['debug'] = $this->debug;

            return response()->json($this->cachedData);
        }
    }

    /**
     * Returns true if the data was initially cached.
     * @return bool
     */
    public function wasCached() {
        return $this->wasCached;
    }
}