<?php

namespace Kumakyoo\OmaLib\Filter;

class UserFilter extends Filter
{
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
        if ($user===null) $this->user = '';
    }

    public function keep($e) : bool
    {
        return is_numeric($this->user)?($this->user==$e->uid):($this->user===$e->user);
    }

    public function countable() : bool
    {
        return false;
    }
}
