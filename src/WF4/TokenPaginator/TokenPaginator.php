<?php


namespace WF4\TokenPaginator;

use ArrayAccess;
use Closure;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use IteratorAggregate;
use JsonSerializable;
use WF4\TokenPaginator\Constracts\TokenPaginator as TokenPaginatorContract;

class TokenPaginator extends AbstractPaginator implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Jsonable, TokenPaginatorContract
{
    /**
     * @var string
     */
    protected $tokenName;

    /**
     * @var string|null
     */
    protected $nextToken;

    /**
     * @var string|null
     */
    protected $prevToken;

    /**
     * @var string|null
     */
    protected $currentToken;

    /**
     * @var \Closure
     */
    protected static $currentTokenResolver;

    /**
     * TokenPaginator constructor.
     * @param mixed        $items
     * @param string|null  $nextToken
     * @param string|null  $prevToken
     * @param int          $perPage
     * @param string       $tokenName
     * @param string|null  $currentToken
     * @param array        $options
     */
    public function __construct($items, $nextToken, $prevToken, $perPage, $tokenName = 'token', $currentToken = null, array $options = [])
    {
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->perPage      = $perPage;
        $this->tokenName     = $tokenName;
        $this->nextToken    = $nextToken;
        $this->prevToken    = $prevToken;
        $this->currentToken = $this->setCurrentToken($currentToken, $tokenName);
        $this->path         = $this->path !== '/' ? rtrim($this->path, '/') : $this->path;
        $this->items        = $items instanceof Collection ? $items : Collection::make($items);
    }

    /**
     * @return bool
     */
    public function hasPrev()
    {
        return (bool) $this->prevToken;
    }

    /**
     * @return bool
     */
    public function hasNext()
    {
        return (bool) $this->nextToken;
    }

    /**
     * @return bool
     */
    public function hasMorePages()
    {
        return $this->hasNext();
    }

    /**
     * @return string|null
     */
    public function previousPageUrl()
    {
        if ($this->hasPrev()) {
            return $this->url($this->prevToken);
        }
    }

    /**
     * @return string|null
     */
    public function nextPageUrl()
    {
        if ($this->hasNext()) {
            return $this->url($this->nextToken);
        }
    }

    /**
     * @return string|null
     */
    public function currentToken()
    {
        return $this->currentToken;
    }

    /**
     * @return string|null
     */
    public function prevToken()
    {
        return $this->prevToken;
    }

    /**
     * @return string|null
     */
    public function nextToken()
    {
        return $this->nextToken;
    }

    /**
     * Determine if there are enough items to split into multiple pages.
     *
     * @return bool
     */
    public function hasPages()
    {
        return $this->hasPrev() || $this->hasNext();
    }

    /**
     * Determine if the paginator is on the first page.
     *
     * @return bool
     */
    public function onFirstPage()
    {
        return (! $this->hasPrev());
    }

    /**
     * @return string
     */
    public function getTokenName()
    {
        return $this->tokenName;
    }

    /**
     * @param  string|null  $currentToken
     * @param  string       $tokenName
     * @return string
     */
    protected function setCurrentToken($currentToken, $tokenName)
    {
        return $currentToken ?: static::resolveCurrentToken($tokenName);
    }

    /**
     * @param  string       $tokenName
     * @param  string|null  $default
     * @return string|null
     */
    public static function resolveCurrentToken($tokenName = 'token', $default = null)
    {
        if (static::$currentTokenResolver) {
            return call_user_func(static::$currentTokenResolver, $tokenName);
        }

        return $default;
    }

    /**
     * @param \Closure $resolver
     */
    public static function setCurrentTokenResolver(Closure $resolver)
    {
        static::$currentTokenResolver = $resolver;
    }

    /**
     * Get the URL for a given page number.
     *
     * @param  string  $token
     * @return string
     */
    public function url($token)
    {
        $parameters = [$this->tokenName => $token];

        if (count($this->query) > 0) {
            $parameters = array_merge($this->query, $parameters);
        }

        return $this->path
            .(Str::contains($this->path, '?') ? '&' : '?')
            .http_build_query($parameters, '', '&')
            .$this->buildFragment();
    }

    /**
     * Render the paginator using the given view.
     *
     * @param  string|null  $view
     * @param  array  $data
     * @return string
     */
    public function links($view = null, $data = [])
    {
        return $this->render($view, $data);
    }

    /**
     * Render the paginator using the given view.
     *
     * @param  string|null  $view
     * @param  array  $data
     * @return string
     */
    public function render($view = null, $data = [])
    {
        return new HtmlString(
            static::viewFactory()->make($view ?: static::$defaultSimpleView, array_merge($data, [
                'paginator' => $this,
            ]))->render()
        );
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'current_token' => $this->currentToken(),
            'data' => $this->items->toArray(),
            'from' => $this->firstItem(),
            'next_page_url' => $this->nextPageUrl(),
            'path' => $this->path,
            'per_page' => $this->perPage(),
            'prev_page_url' => $this->previousPageUrl(),
            'to' => $this->lastItem(),
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}