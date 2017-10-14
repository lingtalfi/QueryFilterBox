<?php


namespace QueryFilterBox\ItemsGenerator;


use Kamille\Services\XLog;
use QueryFilterBox\Query\Query;
use QueryFilterBox\Query\QueryInterface;
use QueryFilterBox\QueryFilterBox\QueryFilterBoxInterface;
use QueryFilterBox\Util\Paginator\Paginator;
use QueryFilterBox\Util\Paginator\PaginatorInterface;
use QuickPdo\QuickPdo;

class ItemsGenerator implements ItemsGeneratorInterface
{
    /**
     * @var QueryFilterBoxInterface[]
     */
    private $filterBoxes;

    /**
     * @var QueryInterface $query
     */
    private $query;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    //
    private $_nbTotalItems;
    private $_usedPool;

    public function __construct()
    {
        $this->filterBoxes = [];
        $this->query = null;
    }

    public static function create()
    {
        return new static();
    }

    public function setFilterBox($name, QueryFilterBoxInterface $filterBox)
    {
        $this->filterBoxes[$name] = $filterBox;
        return $this;
    }


    public function getFilterBox($name)
    {
        return $this->filterBoxes[$name];
    }

    public function getItems(array $pool, $fetchStyle = null)
    {
        $query = $this->getQuery();
        $usedPool = [];
        foreach ($this->filterBoxes as $filterBox) {
            $filterBox->decorateQuery($query, $pool, $usedPool);
        }

        $markers = $query->getMarkers();
        $countQuery = $query->getCountQuery();
        $nbTotalItems = QuickPdo::fetch($countQuery, $markers, \PDO::FETCH_COLUMN);
        $this->_nbTotalItems = $nbTotalItems;

        if (null !== $this->paginator) {
            $this->paginator->decorateQuery($query, $nbTotalItems, $pool, $usedPool);
        }


        $usedPool = array_unique($usedPool);
        $this->_usedPool = array_intersect_key($pool, array_flip($usedPool));


        $q = $query->getQuery();
        az(__FILE__, $q);
        $items = QuickPdo::fetchAll($q, $markers, $fetchStyle);
        foreach ($this->filterBoxes as $filterBox) {
            $filterBox->setItems($items);
            $filterBox->setUsedPool($usedPool);
            $filterBox->prepare();
        }

        return $items;
    }

    //--------------------------------------------
    //
    //--------------------------------------------
    public function setQuery(QueryInterface $query)
    {
        $this->query = $query;
        return $this;
    }

    public function setPaginator(PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;
        return $this;
    }

    public function getInfo()
    {
        if ($this->paginator instanceof Paginator) {
            $model = $this->paginator->getModel();
            $nipp = $model['nipp'];
            $page = $model['page'];
        } else {
            $nipp = 20;
            $page = 1;
        }


        return [
            'nipp' => $nipp,
            'nbTotalItems' => $this->_nbTotalItems,
            'page' => $page,
            'pool' => $this->_usedPool,
        ];
    }


    //--------------------------------------------
    //
    //--------------------------------------------
    protected function getQuery()
    {
        if (null === $this->query) {
            $this->query = new Query();
        }
        return $this->query;
    }


}


