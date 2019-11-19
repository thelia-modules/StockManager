<?php

namespace StockManager\Model\Base;

use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveQuery\ModelJoin;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use StockManager\Model\StockOperation as ChildStockOperation;
use StockManager\Model\StockOperationQuery as ChildStockOperationQuery;
use StockManager\Model\Map\StockOperationTableMap;

/**
 * Base class that represents a query for the 'stock_operation' table.
 *
 *
 *
 * @method     ChildStockOperationQuery orderById($order = Criteria::ASC) Order by the id column
 * @method     ChildStockOperationQuery orderByOperation($order = Criteria::ASC) Order by the operation column
 * @method     ChildStockOperationQuery orderByCreatedAt($order = Criteria::ASC) Order by the created_at column
 * @method     ChildStockOperationQuery orderByUpdatedAt($order = Criteria::ASC) Order by the updated_at column
 *
 * @method     ChildStockOperationQuery groupById() Group by the id column
 * @method     ChildStockOperationQuery groupByOperation() Group by the operation column
 * @method     ChildStockOperationQuery groupByCreatedAt() Group by the created_at column
 * @method     ChildStockOperationQuery groupByUpdatedAt() Group by the updated_at column
 *
 * @method     ChildStockOperationQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildStockOperationQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildStockOperationQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildStockOperationQuery leftJoinStockOperationSourceStatus($relationAlias = null) Adds a LEFT JOIN clause to the query using the StockOperationSourceStatus relation
 * @method     ChildStockOperationQuery rightJoinStockOperationSourceStatus($relationAlias = null) Adds a RIGHT JOIN clause to the query using the StockOperationSourceStatus relation
 * @method     ChildStockOperationQuery innerJoinStockOperationSourceStatus($relationAlias = null) Adds a INNER JOIN clause to the query using the StockOperationSourceStatus relation
 *
 * @method     ChildStockOperationQuery leftJoinStockOperationTargetStatus($relationAlias = null) Adds a LEFT JOIN clause to the query using the StockOperationTargetStatus relation
 * @method     ChildStockOperationQuery rightJoinStockOperationTargetStatus($relationAlias = null) Adds a RIGHT JOIN clause to the query using the StockOperationTargetStatus relation
 * @method     ChildStockOperationQuery innerJoinStockOperationTargetStatus($relationAlias = null) Adds a INNER JOIN clause to the query using the StockOperationTargetStatus relation
 *
 * @method     ChildStockOperationQuery leftJoinStockOperationPaymentModule($relationAlias = null) Adds a LEFT JOIN clause to the query using the StockOperationPaymentModule relation
 * @method     ChildStockOperationQuery rightJoinStockOperationPaymentModule($relationAlias = null) Adds a RIGHT JOIN clause to the query using the StockOperationPaymentModule relation
 * @method     ChildStockOperationQuery innerJoinStockOperationPaymentModule($relationAlias = null) Adds a INNER JOIN clause to the query using the StockOperationPaymentModule relation
 *
 * @method     ChildStockOperationQuery leftJoinStockOperationDeliveryModule($relationAlias = null) Adds a LEFT JOIN clause to the query using the StockOperationDeliveryModule relation
 * @method     ChildStockOperationQuery rightJoinStockOperationDeliveryModule($relationAlias = null) Adds a RIGHT JOIN clause to the query using the StockOperationDeliveryModule relation
 * @method     ChildStockOperationQuery innerJoinStockOperationDeliveryModule($relationAlias = null) Adds a INNER JOIN clause to the query using the StockOperationDeliveryModule relation
 *
 * @method     ChildStockOperation findOne(ConnectionInterface $con = null) Return the first ChildStockOperation matching the query
 * @method     ChildStockOperation findOneOrCreate(ConnectionInterface $con = null) Return the first ChildStockOperation matching the query, or a new ChildStockOperation object populated from the query conditions when no match is found
 *
 * @method     ChildStockOperation findOneById(int $id) Return the first ChildStockOperation filtered by the id column
 * @method     ChildStockOperation findOneByOperation(string $operation) Return the first ChildStockOperation filtered by the operation column
 * @method     ChildStockOperation findOneByCreatedAt(string $created_at) Return the first ChildStockOperation filtered by the created_at column
 * @method     ChildStockOperation findOneByUpdatedAt(string $updated_at) Return the first ChildStockOperation filtered by the updated_at column
 *
 * @method     array findById(int $id) Return ChildStockOperation objects filtered by the id column
 * @method     array findByOperation(string $operation) Return ChildStockOperation objects filtered by the operation column
 * @method     array findByCreatedAt(string $created_at) Return ChildStockOperation objects filtered by the created_at column
 * @method     array findByUpdatedAt(string $updated_at) Return ChildStockOperation objects filtered by the updated_at column
 *
 */
abstract class StockOperationQuery extends ModelCriteria
{

    /**
     * Initializes internal state of \StockManager\Model\Base\StockOperationQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'thelia', $modelName = '\\StockManager\\Model\\StockOperation', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildStockOperationQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildStockOperationQuery
     */
    public static function create($modelAlias = null, $criteria = null)
    {
        if ($criteria instanceof \StockManager\Model\StockOperationQuery) {
            return $criteria;
        }
        $query = new \StockManager\Model\StockOperationQuery();
        if (null !== $modelAlias) {
            $query->setModelAlias($modelAlias);
        }
        if ($criteria instanceof Criteria) {
            $query->mergeWith($criteria);
        }

        return $query;
    }

    /**
     * Find object by primary key.
     * Propel uses the instance pool to skip the database if the object exists.
     * Go fast if the query is untouched.
     *
     * <code>
     * $obj  = $c->findPk(12, $con);
     * </code>
     *
     * @param mixed $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildStockOperation|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = StockOperationTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(StockOperationTableMap::DATABASE_NAME);
        }
        $this->basePreSelect($con);
        if ($this->formatter || $this->modelAlias || $this->with || $this->select
         || $this->selectColumns || $this->asColumns || $this->selectModifiers
         || $this->map || $this->having || $this->joins) {
            return $this->findPkComplex($key, $con);
        } else {
            return $this->findPkSimple($key, $con);
        }
    }

    /**
     * Find object by primary key using raw SQL to go fast.
     * Bypass doSelect() and the object formatter by using generated code.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @return   ChildStockOperation A model object, or null if the key is not found
     */
    protected function findPkSimple($key, $con)
    {
        $sql = 'SELECT ID, OPERATION, CREATED_AT, UPDATED_AT FROM stock_operation WHERE ID = :p0';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $obj = new ChildStockOperation();
            $obj->hydrate($row);
            StockOperationTableMap::addInstanceToPool($obj, (string) $key);
        }
        $stmt->closeCursor();

        return $obj;
    }

    /**
     * Find object by primary key.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @return ChildStockOperation|array|mixed the result, formatted by the current formatter
     */
    protected function findPkComplex($key, $con)
    {
        // As the query uses a PK condition, no limit(1) is necessary.
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKey($key)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->formatOne($dataFetcher);
    }

    /**
     * Find objects by primary key
     * <code>
     * $objs = $c->findPks(array(12, 56, 832), $con);
     * </code>
     * @param     array $keys Primary keys to use for the query
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ObjectCollection|array|mixed the list of results, formatted by the current formatter
     */
    public function findPks($keys, $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }
        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKeys($keys)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->format($dataFetcher);
    }

    /**
     * Filter the query by primary key
     *
     * @param     mixed $key Primary key to use for the query
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(StockOperationTableMap::ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(StockOperationTableMap::ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the id column
     *
     * Example usage:
     * <code>
     * $query->filterById(1234); // WHERE id = 1234
     * $query->filterById(array(12, 34)); // WHERE id IN (12, 34)
     * $query->filterById(array('min' => 12)); // WHERE id > 12
     * </code>
     *
     * @param     mixed $id The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function filterById($id = null, $comparison = null)
    {
        if (is_array($id)) {
            $useMinMax = false;
            if (isset($id['min'])) {
                $this->addUsingAlias(StockOperationTableMap::ID, $id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($id['max'])) {
                $this->addUsingAlias(StockOperationTableMap::ID, $id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(StockOperationTableMap::ID, $id, $comparison);
    }

    /**
     * Filter the query on the operation column
     *
     * Example usage:
     * <code>
     * $query->filterByOperation('fooValue');   // WHERE operation = 'fooValue'
     * $query->filterByOperation('%fooValue%'); // WHERE operation LIKE '%fooValue%'
     * </code>
     *
     * @param     string $operation The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function filterByOperation($operation = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($operation)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $operation)) {
                $operation = str_replace('*', '%', $operation);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(StockOperationTableMap::OPERATION, $operation, $comparison);
    }

    /**
     * Filter the query on the created_at column
     *
     * Example usage:
     * <code>
     * $query->filterByCreatedAt('2011-03-14'); // WHERE created_at = '2011-03-14'
     * $query->filterByCreatedAt('now'); // WHERE created_at = '2011-03-14'
     * $query->filterByCreatedAt(array('max' => 'yesterday')); // WHERE created_at > '2011-03-13'
     * </code>
     *
     * @param     mixed $createdAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function filterByCreatedAt($createdAt = null, $comparison = null)
    {
        if (is_array($createdAt)) {
            $useMinMax = false;
            if (isset($createdAt['min'])) {
                $this->addUsingAlias(StockOperationTableMap::CREATED_AT, $createdAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($createdAt['max'])) {
                $this->addUsingAlias(StockOperationTableMap::CREATED_AT, $createdAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(StockOperationTableMap::CREATED_AT, $createdAt, $comparison);
    }

    /**
     * Filter the query on the updated_at column
     *
     * Example usage:
     * <code>
     * $query->filterByUpdatedAt('2011-03-14'); // WHERE updated_at = '2011-03-14'
     * $query->filterByUpdatedAt('now'); // WHERE updated_at = '2011-03-14'
     * $query->filterByUpdatedAt(array('max' => 'yesterday')); // WHERE updated_at > '2011-03-13'
     * </code>
     *
     * @param     mixed $updatedAt The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function filterByUpdatedAt($updatedAt = null, $comparison = null)
    {
        if (is_array($updatedAt)) {
            $useMinMax = false;
            if (isset($updatedAt['min'])) {
                $this->addUsingAlias(StockOperationTableMap::UPDATED_AT, $updatedAt['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($updatedAt['max'])) {
                $this->addUsingAlias(StockOperationTableMap::UPDATED_AT, $updatedAt['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(StockOperationTableMap::UPDATED_AT, $updatedAt, $comparison);
    }

    /**
     * Filter the query by a related \StockManager\Model\StockOperationSourceStatus object
     *
     * @param \StockManager\Model\StockOperationSourceStatus|ObjectCollection $stockOperationSourceStatus  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function filterByStockOperationSourceStatus($stockOperationSourceStatus, $comparison = null)
    {
        if ($stockOperationSourceStatus instanceof \StockManager\Model\StockOperationSourceStatus) {
            return $this
                ->addUsingAlias(StockOperationTableMap::ID, $stockOperationSourceStatus->getStockOperationId(), $comparison);
        } elseif ($stockOperationSourceStatus instanceof ObjectCollection) {
            return $this
                ->useStockOperationSourceStatusQuery()
                ->filterByPrimaryKeys($stockOperationSourceStatus->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByStockOperationSourceStatus() only accepts arguments of type \StockManager\Model\StockOperationSourceStatus or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the StockOperationSourceStatus relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function joinStockOperationSourceStatus($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('StockOperationSourceStatus');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'StockOperationSourceStatus');
        }

        return $this;
    }

    /**
     * Use the StockOperationSourceStatus relation StockOperationSourceStatus object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return   \StockManager\Model\StockOperationSourceStatusQuery A secondary query class using the current class as primary query
     */
    public function useStockOperationSourceStatusQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinStockOperationSourceStatus($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'StockOperationSourceStatus', '\StockManager\Model\StockOperationSourceStatusQuery');
    }

    /**
     * Filter the query by a related \StockManager\Model\StockOperationTargetStatus object
     *
     * @param \StockManager\Model\StockOperationTargetStatus|ObjectCollection $stockOperationTargetStatus  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function filterByStockOperationTargetStatus($stockOperationTargetStatus, $comparison = null)
    {
        if ($stockOperationTargetStatus instanceof \StockManager\Model\StockOperationTargetStatus) {
            return $this
                ->addUsingAlias(StockOperationTableMap::ID, $stockOperationTargetStatus->getStockOperationId(), $comparison);
        } elseif ($stockOperationTargetStatus instanceof ObjectCollection) {
            return $this
                ->useStockOperationTargetStatusQuery()
                ->filterByPrimaryKeys($stockOperationTargetStatus->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByStockOperationTargetStatus() only accepts arguments of type \StockManager\Model\StockOperationTargetStatus or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the StockOperationTargetStatus relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function joinStockOperationTargetStatus($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('StockOperationTargetStatus');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'StockOperationTargetStatus');
        }

        return $this;
    }

    /**
     * Use the StockOperationTargetStatus relation StockOperationTargetStatus object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return   \StockManager\Model\StockOperationTargetStatusQuery A secondary query class using the current class as primary query
     */
    public function useStockOperationTargetStatusQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinStockOperationTargetStatus($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'StockOperationTargetStatus', '\StockManager\Model\StockOperationTargetStatusQuery');
    }

    /**
     * Filter the query by a related \StockManager\Model\StockOperationPaymentModule object
     *
     * @param \StockManager\Model\StockOperationPaymentModule|ObjectCollection $stockOperationPaymentModule  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function filterByStockOperationPaymentModule($stockOperationPaymentModule, $comparison = null)
    {
        if ($stockOperationPaymentModule instanceof \StockManager\Model\StockOperationPaymentModule) {
            return $this
                ->addUsingAlias(StockOperationTableMap::ID, $stockOperationPaymentModule->getStockOperationId(), $comparison);
        } elseif ($stockOperationPaymentModule instanceof ObjectCollection) {
            return $this
                ->useStockOperationPaymentModuleQuery()
                ->filterByPrimaryKeys($stockOperationPaymentModule->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByStockOperationPaymentModule() only accepts arguments of type \StockManager\Model\StockOperationPaymentModule or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the StockOperationPaymentModule relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function joinStockOperationPaymentModule($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('StockOperationPaymentModule');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'StockOperationPaymentModule');
        }

        return $this;
    }

    /**
     * Use the StockOperationPaymentModule relation StockOperationPaymentModule object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return   \StockManager\Model\StockOperationPaymentModuleQuery A secondary query class using the current class as primary query
     */
    public function useStockOperationPaymentModuleQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinStockOperationPaymentModule($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'StockOperationPaymentModule', '\StockManager\Model\StockOperationPaymentModuleQuery');
    }

    /**
     * Filter the query by a related \StockManager\Model\StockOperationDeliveryModule object
     *
     * @param \StockManager\Model\StockOperationDeliveryModule|ObjectCollection $stockOperationDeliveryModule  the related object to use as filter
     * @param string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function filterByStockOperationDeliveryModule($stockOperationDeliveryModule, $comparison = null)
    {
        if ($stockOperationDeliveryModule instanceof \StockManager\Model\StockOperationDeliveryModule) {
            return $this
                ->addUsingAlias(StockOperationTableMap::ID, $stockOperationDeliveryModule->getStockOperationId(), $comparison);
        } elseif ($stockOperationDeliveryModule instanceof ObjectCollection) {
            return $this
                ->useStockOperationDeliveryModuleQuery()
                ->filterByPrimaryKeys($stockOperationDeliveryModule->getPrimaryKeys())
                ->endUse();
        } else {
            throw new PropelException('filterByStockOperationDeliveryModule() only accepts arguments of type \StockManager\Model\StockOperationDeliveryModule or Collection');
        }
    }

    /**
     * Adds a JOIN clause to the query using the StockOperationDeliveryModule relation
     *
     * @param     string $relationAlias optional alias for the relation
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function joinStockOperationDeliveryModule($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        $tableMap = $this->getTableMap();
        $relationMap = $tableMap->getRelation('StockOperationDeliveryModule');

        // create a ModelJoin object for this join
        $join = new ModelJoin();
        $join->setJoinType($joinType);
        $join->setRelationMap($relationMap, $this->useAliasInSQL ? $this->getModelAlias() : null, $relationAlias);
        if ($previousJoin = $this->getPreviousJoin()) {
            $join->setPreviousJoin($previousJoin);
        }

        // add the ModelJoin to the current object
        if ($relationAlias) {
            $this->addAlias($relationAlias, $relationMap->getRightTable()->getName());
            $this->addJoinObject($join, $relationAlias);
        } else {
            $this->addJoinObject($join, 'StockOperationDeliveryModule');
        }

        return $this;
    }

    /**
     * Use the StockOperationDeliveryModule relation StockOperationDeliveryModule object
     *
     * @see useQuery()
     *
     * @param     string $relationAlias optional alias for the relation,
     *                                   to be used as main alias in the secondary query
     * @param     string $joinType Accepted values are null, 'left join', 'right join', 'inner join'
     *
     * @return   \StockManager\Model\StockOperationDeliveryModuleQuery A secondary query class using the current class as primary query
     */
    public function useStockOperationDeliveryModuleQuery($relationAlias = null, $joinType = Criteria::INNER_JOIN)
    {
        return $this
            ->joinStockOperationDeliveryModule($relationAlias, $joinType)
            ->useQuery($relationAlias ? $relationAlias : 'StockOperationDeliveryModule', '\StockManager\Model\StockOperationDeliveryModuleQuery');
    }

    /**
     * Exclude object from result
     *
     * @param   ChildStockOperation $stockOperation Object to remove from the list of results
     *
     * @return ChildStockOperationQuery The current query, for fluid interface
     */
    public function prune($stockOperation = null)
    {
        if ($stockOperation) {
            $this->addUsingAlias(StockOperationTableMap::ID, $stockOperation->getId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the stock_operation table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(StockOperationTableMap::DATABASE_NAME);
        }
        $affectedRows = 0; // initialize var to track total num of affected rows
        try {
            // use transaction because $criteria could contain info
            // for more than one table or we could emulating ON DELETE CASCADE, etc.
            $con->beginTransaction();
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            StockOperationTableMap::clearInstancePool();
            StockOperationTableMap::clearRelatedInstancePool();

            $con->commit();
        } catch (PropelException $e) {
            $con->rollBack();
            throw $e;
        }

        return $affectedRows;
    }

    /**
     * Performs a DELETE on the database, given a ChildStockOperation or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or ChildStockOperation object or primary key or array of primary keys
     *              which is used to create the DELETE statement
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *         rethrown wrapped into a PropelException.
     */
     public function delete(ConnectionInterface $con = null)
     {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(StockOperationTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(StockOperationTableMap::DATABASE_NAME);

        $affectedRows = 0; // initialize var to track total num of affected rows

        try {
            // use transaction because $criteria could contain info
            // for more than one table or we could emulating ON DELETE CASCADE, etc.
            $con->beginTransaction();


        StockOperationTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            StockOperationTableMap::clearRelatedInstancePool();
            $con->commit();

            return $affectedRows;
        } catch (PropelException $e) {
            $con->rollBack();
            throw $e;
        }
    }

    // timestampable behavior

    /**
     * Filter by the latest updated
     *
     * @param      int $nbDays Maximum age of the latest update in days
     *
     * @return     ChildStockOperationQuery The current query, for fluid interface
     */
    public function recentlyUpdated($nbDays = 7)
    {
        return $this->addUsingAlias(StockOperationTableMap::UPDATED_AT, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Filter by the latest created
     *
     * @param      int $nbDays Maximum age of in days
     *
     * @return     ChildStockOperationQuery The current query, for fluid interface
     */
    public function recentlyCreated($nbDays = 7)
    {
        return $this->addUsingAlias(StockOperationTableMap::CREATED_AT, time() - $nbDays * 24 * 60 * 60, Criteria::GREATER_EQUAL);
    }

    /**
     * Order by update date desc
     *
     * @return     ChildStockOperationQuery The current query, for fluid interface
     */
    public function lastUpdatedFirst()
    {
        return $this->addDescendingOrderByColumn(StockOperationTableMap::UPDATED_AT);
    }

    /**
     * Order by update date asc
     *
     * @return     ChildStockOperationQuery The current query, for fluid interface
     */
    public function firstUpdatedFirst()
    {
        return $this->addAscendingOrderByColumn(StockOperationTableMap::UPDATED_AT);
    }

    /**
     * Order by create date desc
     *
     * @return     ChildStockOperationQuery The current query, for fluid interface
     */
    public function lastCreatedFirst()
    {
        return $this->addDescendingOrderByColumn(StockOperationTableMap::CREATED_AT);
    }

    /**
     * Order by create date asc
     *
     * @return     ChildStockOperationQuery The current query, for fluid interface
     */
    public function firstCreatedFirst()
    {
        return $this->addAscendingOrderByColumn(StockOperationTableMap::CREATED_AT);
    }

} // StockOperationQuery
