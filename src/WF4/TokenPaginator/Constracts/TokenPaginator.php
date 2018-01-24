<?php

namespace WF4\TokenPaginator\Constracts;

interface TokenPaginator
{
    /**
     * @return bool
     */
    public function hasPrev();

    /**
     * @return bool
     */
    public function hasNext();

    /**
     * @return bool
     */
    public function hasMorePages();

    /**
     * @return string|null
     */
    public function currentToken();

    /**
     * @return string|null
     */
    public function prevToken();

    /**
     * @return string|null
     */
    public function nextToken();

    /**
     * @return string
     */
    public function getTokenName();
}