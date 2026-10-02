<?php

class DbQuery extends DbQueryCore
{
    public function orderBy($fields, $prepend = false)
    {
        if (empty($fields)) {
            $this->query['order'] = [];
            return $this;
        }

        if ($prepend) {
            $this->query['order'] = [$fields, ...$this->query['order']];
        } else {
            $this->query['order'][] = $fields;
        }

        return $this;
    }
}