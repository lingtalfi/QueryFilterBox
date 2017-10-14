<?php


namespace QueryFilterBox\Query;


/**
 * Class Query
 * @package QueryFilterBox\Query
 */
class Query implements QueryInterface
{

    private $selects;
    private $joins;
    private $from;
    private $wheres;
    private $groupBy;
    private $orderBy;
    private $markers;
    private $limit;
    private $countString;

    //
    private $br;


    public function __construct()
    {
        $this->markers = [];
        $this->selects = [];
        $this->from = null;
        $this->joins = [];
        $this->wheres = [];
        $this->groupBy = null;
        $this->orderBy = [];
        $this->limit = [];
        $this->br = PHP_EOL;
        $this->countString = "*";
    }

    public static function create()
    {
        return new static();
    }


    public function getQuery()
    {
        $s = $this->getBaseQuery();
        $s .= PHP_EOL;
        if (null !== $this->groupBy) {
            $s .= PHP_EOL . "group by " . $this->groupBy;
        }

        if ($this->orderBy) {
            $s .= PHP_EOL . "order by ";
            $s .= implode($this->br . ", ", $this->orderBy);
        }

        if ($this->limit) {
            list($offset, $rowCount) = $this->limit;
            $s .= PHP_EOL . "limit $offset, $rowCount";
        }
        return $s;
    }

    public function getCountQuery()
    {
        $s = $this->getBaseQuery(true);
        return $s;
    }

    /**
     * @return array
     */
    public function getMarkers()
    {
        return $this->markers;
    }

    //--------------------------------------------
    //
    //--------------------------------------------
    /**
     * @param $string , the string after select
     * @return $this
     */
    public function addSelect($string)
    {
        $this->selects[] = $string;
        return $this;
    }

    /**
     * @param $string , the string after the from
     * Example:
     *          table1 t1
     *
     *
     * @return $this
     */
    public function setFrom($string)
    {
        $this->from = $string;
        return $this;
    }

    /**
     * @param $string , a string containing a join.
     * Example:
     *          inner join table a on a.id=b.product_id
     *
     * @return $this
     */
    public function addJoin($string)
    {
        $this->joins[] = $string;
        return $this;
    }


    /**
     * @param $string , the string AFTER the "order by" keyword
     * @return $this
     */
    public function addOrderBy($string)
    {
        $this->orderBy[] = $string;
        return $this;
    }


    /**
     * @param $string , the string AFTER the where
     * @param null $ifPrefix , if it has previous statements before, the keyword
     *              used to combine the string with the previous statement.
     *              It can be one of "or" or "and".
     *              Default is "and".
     * @return $this
     */
    public function addWhere($string, $ifPrefix = null)
    {
        $this->wheres[] = [$string, $ifPrefix];
        return $this;
    }

    /**
     * @param $string , the string AFTER the group by keyword.
     * @return $this
     */
    public function setGroupBy($string)
    {
        $this->groupBy = $string;
        return $this;
    }


    public function addMarker($key, $value)
    {
        $this->markers[$key] = $value;
        return $this;
    }

    public function setLimit($offset, $rowCount)
    {
        $this->limit = [$offset, $rowCount];
        return $this;
    }

    public function setCountString($countString)
    {
        $this->countString = $countString;
        return $this;
    }



    //--------------------------------------------
    //
    //--------------------------------------------
    private function getBaseQuery($isCount = false)
    {
        $br = $this->br;
        $s = "select";


        if (false === $isCount) {
            $s .= $br . implode($br, $this->selects);
        } else {
            $s .= $br . "count(". $this->countString .") as count";
        }


        if (null !== $this->from) {
            $s .= $br . "from " . $this->from;
        }
        if ($this->joins) {
            $s .= implode($br, $this->joins);
        }
        if ($this->wheres) {
            $s .= $br . "where" . $br;
            $c = 0;
            foreach ($this->wheres as $where) {
                list($string, $ifPrefix) = $where;
                if (0 !== $c++) {
                    if (null === $ifPrefix) {
                        $ifPrefix = $br . 'and';
                    }
                    $s .= " " . $ifPrefix . " ";
                }
                $s .= $string;
            }
        }
        return $s;
    }
}



