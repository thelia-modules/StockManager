<?php

namespace StockManager\Model\Base;

use \DateTime;
use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Util\PropelDateTime;
use StockManager\Model\StockOperation as ChildStockOperation;
use StockManager\Model\StockOperationDeliveryModule as ChildStockOperationDeliveryModule;
use StockManager\Model\StockOperationDeliveryModuleQuery as ChildStockOperationDeliveryModuleQuery;
use StockManager\Model\StockOperationPaymentModule as ChildStockOperationPaymentModule;
use StockManager\Model\StockOperationPaymentModuleQuery as ChildStockOperationPaymentModuleQuery;
use StockManager\Model\StockOperationQuery as ChildStockOperationQuery;
use StockManager\Model\StockOperationSourceStatus as ChildStockOperationSourceStatus;
use StockManager\Model\StockOperationSourceStatusQuery as ChildStockOperationSourceStatusQuery;
use StockManager\Model\StockOperationTargetStatus as ChildStockOperationTargetStatus;
use StockManager\Model\StockOperationTargetStatusQuery as ChildStockOperationTargetStatusQuery;
use StockManager\Model\Map\StockOperationTableMap;

abstract class StockOperation implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\StockManager\\Model\\Map\\StockOperationTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the id field.
     * @var        int
     */
    protected $id;

    /**
     * The value for the operation field.
     * @var        string
     */
    protected $operation;

    /**
     * The value for the created_at field.
     * @var        string
     */
    protected $created_at;

    /**
     * The value for the updated_at field.
     * @var        string
     */
    protected $updated_at;

    /**
     * @var        ObjectCollection|ChildStockOperationSourceStatus[] Collection to store aggregation of ChildStockOperationSourceStatus objects.
     */
    protected $collStockOperationSourceStatuses;
    protected $collStockOperationSourceStatusesPartial;

    /**
     * @var        ObjectCollection|ChildStockOperationTargetStatus[] Collection to store aggregation of ChildStockOperationTargetStatus objects.
     */
    protected $collStockOperationTargetStatuses;
    protected $collStockOperationTargetStatusesPartial;

    /**
     * @var        ObjectCollection|ChildStockOperationPaymentModule[] Collection to store aggregation of ChildStockOperationPaymentModule objects.
     */
    protected $collStockOperationPaymentModules;
    protected $collStockOperationPaymentModulesPartial;

    /**
     * @var        ObjectCollection|ChildStockOperationDeliveryModule[] Collection to store aggregation of ChildStockOperationDeliveryModule objects.
     */
    protected $collStockOperationDeliveryModules;
    protected $collStockOperationDeliveryModulesPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $stockOperationSourceStatusesScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $stockOperationTargetStatusesScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $stockOperationPaymentModulesScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $stockOperationDeliveryModulesScheduledForDeletion = null;

    /**
     * Initializes internal state of StockManager\Model\Base\StockOperation object.
     */
    public function __construct()
    {
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !!$this->modifiedColumns;
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return $this->modifiedColumns && isset($this->modifiedColumns[$col]);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return $this->modifiedColumns ? array_keys($this->modifiedColumns) : [];
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return boolean true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (Boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (Boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            if (isset($this->modifiedColumns[$col])) {
                unset($this->modifiedColumns[$col]);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>StockOperation</code> instance.  If
     * <code>obj</code> is an instance of <code>StockOperation</code>, delegates to
     * <code>equals(StockOperation)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param  mixed   $obj The object to compare to.
     * @return boolean Whether equal to the object specified.
     */
    public function equals($obj)
    {
        $thisclazz = get_class($this);
        if (!is_object($obj) || !($obj instanceof $thisclazz)) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey()
            || null === $obj->getPrimaryKey())  {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        if (null !== $this->getPrimaryKey()) {
            return crc32(serialize($this->getPrimaryKey()));
        }

        return crc32(serialize(clone $this));
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param  string  $name The virtual column name
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param  string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return StockOperation The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     *
     * @return StockOperation The current object, for fluid interface
     */
    public function importFrom($parser, $data)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data), TableMap::TYPE_PHPNAME);

        return $this;
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        return array_keys(get_object_vars($this));
    }

    /**
     * Get the [id] column value.
     *
     * @return   int
     */
    public function getId()
    {

        return $this->id;
    }

    /**
     * Get the [operation] column value.
     *
     * @return   string
     */
    public function getOperation()
    {

        return $this->operation;
    }

    /**
     * Get the [optionally formatted] temporal [created_at] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or \DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getCreatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->created_at;
        } else {
            return $this->created_at instanceof \DateTime ? $this->created_at->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [updated_at] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or \DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getUpdatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->updated_at;
        } else {
            return $this->updated_at instanceof \DateTime ? $this->updated_at->format($format) : null;
        }
    }

    /**
     * Set the value of [id] column.
     *
     * @param      int $v new value
     * @return   \StockManager\Model\StockOperation The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[StockOperationTableMap::ID] = true;
        }


        return $this;
    } // setId()

    /**
     * Set the value of [operation] column.
     *
     * @param      string $v new value
     * @return   \StockManager\Model\StockOperation The current object (for fluent API support)
     */
    public function setOperation($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->operation !== $v) {
            $this->operation = $v;
            $this->modifiedColumns[StockOperationTableMap::OPERATION] = true;
        }


        return $this;
    } // setOperation()

    /**
     * Sets the value of [created_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \StockManager\Model\StockOperation The current object (for fluent API support)
     */
    public function setCreatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->created_at !== null || $dt !== null) {
            if ($dt !== $this->created_at) {
                $this->created_at = $dt;
                $this->modifiedColumns[StockOperationTableMap::CREATED_AT] = true;
            }
        } // if either are not null


        return $this;
    } // setCreatedAt()

    /**
     * Sets the value of [updated_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \StockManager\Model\StockOperation The current object (for fluent API support)
     */
    public function setUpdatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->updated_at !== null || $dt !== null) {
            if ($dt !== $this->updated_at) {
                $this->updated_at = $dt;
                $this->modifiedColumns[StockOperationTableMap::UPDATED_AT] = true;
            }
        } // if either are not null


        return $this;
    } // setUpdatedAt()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {


            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : StockOperationTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : StockOperationTableMap::translateFieldName('Operation', TableMap::TYPE_PHPNAME, $indexType)];
            $this->operation = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : StockOperationTableMap::translateFieldName('CreatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->created_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : StockOperationTableMap::translateFieldName('UpdatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->updated_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 4; // 4 = StockOperationTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException("Error populating \StockManager\Model\StockOperation object", 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(StockOperationTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildStockOperationQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collStockOperationSourceStatuses = null;

            $this->collStockOperationTargetStatuses = null;

            $this->collStockOperationPaymentModules = null;

            $this->collStockOperationDeliveryModules = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see StockOperation::setDeleted()
     * @see StockOperation::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(StockOperationTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        try {
            $deleteQuery = ChildStockOperationQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $con->commit();
                $this->setDeleted(true);
            } else {
                $con->commit();
            }
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(StockOperationTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        $isInsert = $this->isNew();
        try {
            $ret = $this->preSave($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
                // timestampable behavior
                if (!$this->isColumnModified(StockOperationTableMap::CREATED_AT)) {
                    $this->setCreatedAt(time());
                }
                if (!$this->isColumnModified(StockOperationTableMap::UPDATED_AT)) {
                    $this->setUpdatedAt(time());
                }
            } else {
                $ret = $ret && $this->preUpdate($con);
                // timestampable behavior
                if ($this->isModified() && !$this->isColumnModified(StockOperationTableMap::UPDATED_AT)) {
                    $this->setUpdatedAt(time());
                }
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                StockOperationTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }
            $con->commit();

            return $affectedRows;
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                } else {
                    $this->doUpdate($con);
                }
                $affectedRows += 1;
                $this->resetModified();
            }

            if ($this->stockOperationSourceStatusesScheduledForDeletion !== null) {
                if (!$this->stockOperationSourceStatusesScheduledForDeletion->isEmpty()) {
                    \StockManager\Model\StockOperationSourceStatusQuery::create()
                        ->filterByPrimaryKeys($this->stockOperationSourceStatusesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->stockOperationSourceStatusesScheduledForDeletion = null;
                }
            }

                if ($this->collStockOperationSourceStatuses !== null) {
            foreach ($this->collStockOperationSourceStatuses as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->stockOperationTargetStatusesScheduledForDeletion !== null) {
                if (!$this->stockOperationTargetStatusesScheduledForDeletion->isEmpty()) {
                    \StockManager\Model\StockOperationTargetStatusQuery::create()
                        ->filterByPrimaryKeys($this->stockOperationTargetStatusesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->stockOperationTargetStatusesScheduledForDeletion = null;
                }
            }

                if ($this->collStockOperationTargetStatuses !== null) {
            foreach ($this->collStockOperationTargetStatuses as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->stockOperationPaymentModulesScheduledForDeletion !== null) {
                if (!$this->stockOperationPaymentModulesScheduledForDeletion->isEmpty()) {
                    \StockManager\Model\StockOperationPaymentModuleQuery::create()
                        ->filterByPrimaryKeys($this->stockOperationPaymentModulesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->stockOperationPaymentModulesScheduledForDeletion = null;
                }
            }

                if ($this->collStockOperationPaymentModules !== null) {
            foreach ($this->collStockOperationPaymentModules as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->stockOperationDeliveryModulesScheduledForDeletion !== null) {
                if (!$this->stockOperationDeliveryModulesScheduledForDeletion->isEmpty()) {
                    \StockManager\Model\StockOperationDeliveryModuleQuery::create()
                        ->filterByPrimaryKeys($this->stockOperationDeliveryModulesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->stockOperationDeliveryModulesScheduledForDeletion = null;
                }
            }

                if ($this->collStockOperationDeliveryModules !== null) {
            foreach ($this->collStockOperationDeliveryModules as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[StockOperationTableMap::ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . StockOperationTableMap::ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(StockOperationTableMap::ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(StockOperationTableMap::OPERATION)) {
            $modifiedColumns[':p' . $index++]  = 'OPERATION';
        }
        if ($this->isColumnModified(StockOperationTableMap::CREATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'CREATED_AT';
        }
        if ($this->isColumnModified(StockOperationTableMap::UPDATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'UPDATED_AT';
        }

        $sql = sprintf(
            'INSERT INTO stock_operation (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'ID':
                        $stmt->bindValue($identifier, $this->id, PDO::PARAM_INT);
                        break;
                    case 'OPERATION':
                        $stmt->bindValue($identifier, $this->operation, PDO::PARAM_STR);
                        break;
                    case 'CREATED_AT':
                        $stmt->bindValue($identifier, $this->created_at ? $this->created_at->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'UPDATED_AT':
                        $stmt->bindValue($identifier, $this->updated_at ? $this->updated_at->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = StockOperationTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getId();
                break;
            case 1:
                return $this->getOperation();
                break;
            case 2:
                return $this->getCreatedAt();
                break;
            case 3:
                return $this->getUpdatedAt();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {
        if (isset($alreadyDumpedObjects['StockOperation'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['StockOperation'][$this->getPrimaryKey()] = true;
        $keys = StockOperationTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getOperation(),
            $keys[2] => $this->getCreatedAt(),
            $keys[3] => $this->getUpdatedAt(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->collStockOperationSourceStatuses) {
                $result['StockOperationSourceStatuses'] = $this->collStockOperationSourceStatuses->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collStockOperationTargetStatuses) {
                $result['StockOperationTargetStatuses'] = $this->collStockOperationTargetStatuses->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collStockOperationPaymentModules) {
                $result['StockOperationPaymentModules'] = $this->collStockOperationPaymentModules->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collStockOperationDeliveryModules) {
                $result['StockOperationDeliveryModules'] = $this->collStockOperationDeliveryModules->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param      string $name
     * @param      mixed  $value field value
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return void
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = StockOperationTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @param      mixed $value field value
     * @return void
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setOperation($value);
                break;
            case 2:
                $this->setCreatedAt($value);
                break;
            case 3:
                $this->setUpdatedAt($value);
                break;
        } // switch()
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = StockOperationTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) $this->setId($arr[$keys[0]]);
        if (array_key_exists($keys[1], $arr)) $this->setOperation($arr[$keys[1]]);
        if (array_key_exists($keys[2], $arr)) $this->setCreatedAt($arr[$keys[2]]);
        if (array_key_exists($keys[3], $arr)) $this->setUpdatedAt($arr[$keys[3]]);
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(StockOperationTableMap::DATABASE_NAME);

        if ($this->isColumnModified(StockOperationTableMap::ID)) $criteria->add(StockOperationTableMap::ID, $this->id);
        if ($this->isColumnModified(StockOperationTableMap::OPERATION)) $criteria->add(StockOperationTableMap::OPERATION, $this->operation);
        if ($this->isColumnModified(StockOperationTableMap::CREATED_AT)) $criteria->add(StockOperationTableMap::CREATED_AT, $this->created_at);
        if ($this->isColumnModified(StockOperationTableMap::UPDATED_AT)) $criteria->add(StockOperationTableMap::UPDATED_AT, $this->updated_at);

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = new Criteria(StockOperationTableMap::DATABASE_NAME);
        $criteria->add(StockOperationTableMap::ID, $this->id);

        return $criteria;
    }

    /**
     * Returns the primary key for this object (row).
     * @return   int
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * Generic method to set the primary key (id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {

        return null === $this->getId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \StockManager\Model\StockOperation (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setOperation($this->getOperation());
        $copyObj->setCreatedAt($this->getCreatedAt());
        $copyObj->setUpdatedAt($this->getUpdatedAt());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getStockOperationSourceStatuses() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addStockOperationSourceStatus($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getStockOperationTargetStatuses() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addStockOperationTargetStatus($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getStockOperationPaymentModules() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addStockOperationPaymentModule($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getStockOperationDeliveryModules() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addStockOperationDeliveryModule($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return                 \StockManager\Model\StockOperation Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }


    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('StockOperationSourceStatus' == $relationName) {
            return $this->initStockOperationSourceStatuses();
        }
        if ('StockOperationTargetStatus' == $relationName) {
            return $this->initStockOperationTargetStatuses();
        }
        if ('StockOperationPaymentModule' == $relationName) {
            return $this->initStockOperationPaymentModules();
        }
        if ('StockOperationDeliveryModule' == $relationName) {
            return $this->initStockOperationDeliveryModules();
        }
    }

    /**
     * Clears out the collStockOperationSourceStatuses collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addStockOperationSourceStatuses()
     */
    public function clearStockOperationSourceStatuses()
    {
        $this->collStockOperationSourceStatuses = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collStockOperationSourceStatuses collection loaded partially.
     */
    public function resetPartialStockOperationSourceStatuses($v = true)
    {
        $this->collStockOperationSourceStatusesPartial = $v;
    }

    /**
     * Initializes the collStockOperationSourceStatuses collection.
     *
     * By default this just sets the collStockOperationSourceStatuses collection to an empty array (like clearcollStockOperationSourceStatuses());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initStockOperationSourceStatuses($overrideExisting = true)
    {
        if (null !== $this->collStockOperationSourceStatuses && !$overrideExisting) {
            return;
        }
        $this->collStockOperationSourceStatuses = new ObjectCollection();
        $this->collStockOperationSourceStatuses->setModel('\StockManager\Model\StockOperationSourceStatus');
    }

    /**
     * Gets an array of ChildStockOperationSourceStatus objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildStockOperation is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildStockOperationSourceStatus[] List of ChildStockOperationSourceStatus objects
     * @throws PropelException
     */
    public function getStockOperationSourceStatuses($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collStockOperationSourceStatusesPartial && !$this->isNew();
        if (null === $this->collStockOperationSourceStatuses || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collStockOperationSourceStatuses) {
                // return empty collection
                $this->initStockOperationSourceStatuses();
            } else {
                $collStockOperationSourceStatuses = ChildStockOperationSourceStatusQuery::create(null, $criteria)
                    ->filterByStockOperation($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collStockOperationSourceStatusesPartial && count($collStockOperationSourceStatuses)) {
                        $this->initStockOperationSourceStatuses(false);

                        foreach ($collStockOperationSourceStatuses as $obj) {
                            if (false == $this->collStockOperationSourceStatuses->contains($obj)) {
                                $this->collStockOperationSourceStatuses->append($obj);
                            }
                        }

                        $this->collStockOperationSourceStatusesPartial = true;
                    }

                    reset($collStockOperationSourceStatuses);

                    return $collStockOperationSourceStatuses;
                }

                if ($partial && $this->collStockOperationSourceStatuses) {
                    foreach ($this->collStockOperationSourceStatuses as $obj) {
                        if ($obj->isNew()) {
                            $collStockOperationSourceStatuses[] = $obj;
                        }
                    }
                }

                $this->collStockOperationSourceStatuses = $collStockOperationSourceStatuses;
                $this->collStockOperationSourceStatusesPartial = false;
            }
        }

        return $this->collStockOperationSourceStatuses;
    }

    /**
     * Sets a collection of StockOperationSourceStatus objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $stockOperationSourceStatuses A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildStockOperation The current object (for fluent API support)
     */
    public function setStockOperationSourceStatuses(Collection $stockOperationSourceStatuses, ConnectionInterface $con = null)
    {
        $stockOperationSourceStatusesToDelete = $this->getStockOperationSourceStatuses(new Criteria(), $con)->diff($stockOperationSourceStatuses);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->stockOperationSourceStatusesScheduledForDeletion = clone $stockOperationSourceStatusesToDelete;

        foreach ($stockOperationSourceStatusesToDelete as $stockOperationSourceStatusRemoved) {
            $stockOperationSourceStatusRemoved->setStockOperation(null);
        }

        $this->collStockOperationSourceStatuses = null;
        foreach ($stockOperationSourceStatuses as $stockOperationSourceStatus) {
            $this->addStockOperationSourceStatus($stockOperationSourceStatus);
        }

        $this->collStockOperationSourceStatuses = $stockOperationSourceStatuses;
        $this->collStockOperationSourceStatusesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related StockOperationSourceStatus objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related StockOperationSourceStatus objects.
     * @throws PropelException
     */
    public function countStockOperationSourceStatuses(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collStockOperationSourceStatusesPartial && !$this->isNew();
        if (null === $this->collStockOperationSourceStatuses || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collStockOperationSourceStatuses) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getStockOperationSourceStatuses());
            }

            $query = ChildStockOperationSourceStatusQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByStockOperation($this)
                ->count($con);
        }

        return count($this->collStockOperationSourceStatuses);
    }

    /**
     * Method called to associate a ChildStockOperationSourceStatus object to this object
     * through the ChildStockOperationSourceStatus foreign key attribute.
     *
     * @param    ChildStockOperationSourceStatus $l ChildStockOperationSourceStatus
     * @return   \StockManager\Model\StockOperation The current object (for fluent API support)
     */
    public function addStockOperationSourceStatus(ChildStockOperationSourceStatus $l)
    {
        if ($this->collStockOperationSourceStatuses === null) {
            $this->initStockOperationSourceStatuses();
            $this->collStockOperationSourceStatusesPartial = true;
        }

        if (!in_array($l, $this->collStockOperationSourceStatuses->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddStockOperationSourceStatus($l);
        }

        return $this;
    }

    /**
     * @param StockOperationSourceStatus $stockOperationSourceStatus The stockOperationSourceStatus object to add.
     */
    protected function doAddStockOperationSourceStatus($stockOperationSourceStatus)
    {
        $this->collStockOperationSourceStatuses[]= $stockOperationSourceStatus;
        $stockOperationSourceStatus->setStockOperation($this);
    }

    /**
     * @param  StockOperationSourceStatus $stockOperationSourceStatus The stockOperationSourceStatus object to remove.
     * @return ChildStockOperation The current object (for fluent API support)
     */
    public function removeStockOperationSourceStatus($stockOperationSourceStatus)
    {
        if ($this->getStockOperationSourceStatuses()->contains($stockOperationSourceStatus)) {
            $this->collStockOperationSourceStatuses->remove($this->collStockOperationSourceStatuses->search($stockOperationSourceStatus));
            if (null === $this->stockOperationSourceStatusesScheduledForDeletion) {
                $this->stockOperationSourceStatusesScheduledForDeletion = clone $this->collStockOperationSourceStatuses;
                $this->stockOperationSourceStatusesScheduledForDeletion->clear();
            }
            $this->stockOperationSourceStatusesScheduledForDeletion[]= clone $stockOperationSourceStatus;
            $stockOperationSourceStatus->setStockOperation(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this StockOperation is new, it will return
     * an empty collection; or if this StockOperation has previously
     * been saved, it will retrieve related StockOperationSourceStatuses from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in StockOperation.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildStockOperationSourceStatus[] List of ChildStockOperationSourceStatus objects
     */
    public function getStockOperationSourceStatusesJoinOrderStatus($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildStockOperationSourceStatusQuery::create(null, $criteria);
        $query->joinWith('OrderStatus', $joinBehavior);

        return $this->getStockOperationSourceStatuses($query, $con);
    }

    /**
     * Clears out the collStockOperationTargetStatuses collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addStockOperationTargetStatuses()
     */
    public function clearStockOperationTargetStatuses()
    {
        $this->collStockOperationTargetStatuses = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collStockOperationTargetStatuses collection loaded partially.
     */
    public function resetPartialStockOperationTargetStatuses($v = true)
    {
        $this->collStockOperationTargetStatusesPartial = $v;
    }

    /**
     * Initializes the collStockOperationTargetStatuses collection.
     *
     * By default this just sets the collStockOperationTargetStatuses collection to an empty array (like clearcollStockOperationTargetStatuses());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initStockOperationTargetStatuses($overrideExisting = true)
    {
        if (null !== $this->collStockOperationTargetStatuses && !$overrideExisting) {
            return;
        }
        $this->collStockOperationTargetStatuses = new ObjectCollection();
        $this->collStockOperationTargetStatuses->setModel('\StockManager\Model\StockOperationTargetStatus');
    }

    /**
     * Gets an array of ChildStockOperationTargetStatus objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildStockOperation is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildStockOperationTargetStatus[] List of ChildStockOperationTargetStatus objects
     * @throws PropelException
     */
    public function getStockOperationTargetStatuses($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collStockOperationTargetStatusesPartial && !$this->isNew();
        if (null === $this->collStockOperationTargetStatuses || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collStockOperationTargetStatuses) {
                // return empty collection
                $this->initStockOperationTargetStatuses();
            } else {
                $collStockOperationTargetStatuses = ChildStockOperationTargetStatusQuery::create(null, $criteria)
                    ->filterByStockOperation($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collStockOperationTargetStatusesPartial && count($collStockOperationTargetStatuses)) {
                        $this->initStockOperationTargetStatuses(false);

                        foreach ($collStockOperationTargetStatuses as $obj) {
                            if (false == $this->collStockOperationTargetStatuses->contains($obj)) {
                                $this->collStockOperationTargetStatuses->append($obj);
                            }
                        }

                        $this->collStockOperationTargetStatusesPartial = true;
                    }

                    reset($collStockOperationTargetStatuses);

                    return $collStockOperationTargetStatuses;
                }

                if ($partial && $this->collStockOperationTargetStatuses) {
                    foreach ($this->collStockOperationTargetStatuses as $obj) {
                        if ($obj->isNew()) {
                            $collStockOperationTargetStatuses[] = $obj;
                        }
                    }
                }

                $this->collStockOperationTargetStatuses = $collStockOperationTargetStatuses;
                $this->collStockOperationTargetStatusesPartial = false;
            }
        }

        return $this->collStockOperationTargetStatuses;
    }

    /**
     * Sets a collection of StockOperationTargetStatus objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $stockOperationTargetStatuses A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildStockOperation The current object (for fluent API support)
     */
    public function setStockOperationTargetStatuses(Collection $stockOperationTargetStatuses, ConnectionInterface $con = null)
    {
        $stockOperationTargetStatusesToDelete = $this->getStockOperationTargetStatuses(new Criteria(), $con)->diff($stockOperationTargetStatuses);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->stockOperationTargetStatusesScheduledForDeletion = clone $stockOperationTargetStatusesToDelete;

        foreach ($stockOperationTargetStatusesToDelete as $stockOperationTargetStatusRemoved) {
            $stockOperationTargetStatusRemoved->setStockOperation(null);
        }

        $this->collStockOperationTargetStatuses = null;
        foreach ($stockOperationTargetStatuses as $stockOperationTargetStatus) {
            $this->addStockOperationTargetStatus($stockOperationTargetStatus);
        }

        $this->collStockOperationTargetStatuses = $stockOperationTargetStatuses;
        $this->collStockOperationTargetStatusesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related StockOperationTargetStatus objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related StockOperationTargetStatus objects.
     * @throws PropelException
     */
    public function countStockOperationTargetStatuses(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collStockOperationTargetStatusesPartial && !$this->isNew();
        if (null === $this->collStockOperationTargetStatuses || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collStockOperationTargetStatuses) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getStockOperationTargetStatuses());
            }

            $query = ChildStockOperationTargetStatusQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByStockOperation($this)
                ->count($con);
        }

        return count($this->collStockOperationTargetStatuses);
    }

    /**
     * Method called to associate a ChildStockOperationTargetStatus object to this object
     * through the ChildStockOperationTargetStatus foreign key attribute.
     *
     * @param    ChildStockOperationTargetStatus $l ChildStockOperationTargetStatus
     * @return   \StockManager\Model\StockOperation The current object (for fluent API support)
     */
    public function addStockOperationTargetStatus(ChildStockOperationTargetStatus $l)
    {
        if ($this->collStockOperationTargetStatuses === null) {
            $this->initStockOperationTargetStatuses();
            $this->collStockOperationTargetStatusesPartial = true;
        }

        if (!in_array($l, $this->collStockOperationTargetStatuses->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddStockOperationTargetStatus($l);
        }

        return $this;
    }

    /**
     * @param StockOperationTargetStatus $stockOperationTargetStatus The stockOperationTargetStatus object to add.
     */
    protected function doAddStockOperationTargetStatus($stockOperationTargetStatus)
    {
        $this->collStockOperationTargetStatuses[]= $stockOperationTargetStatus;
        $stockOperationTargetStatus->setStockOperation($this);
    }

    /**
     * @param  StockOperationTargetStatus $stockOperationTargetStatus The stockOperationTargetStatus object to remove.
     * @return ChildStockOperation The current object (for fluent API support)
     */
    public function removeStockOperationTargetStatus($stockOperationTargetStatus)
    {
        if ($this->getStockOperationTargetStatuses()->contains($stockOperationTargetStatus)) {
            $this->collStockOperationTargetStatuses->remove($this->collStockOperationTargetStatuses->search($stockOperationTargetStatus));
            if (null === $this->stockOperationTargetStatusesScheduledForDeletion) {
                $this->stockOperationTargetStatusesScheduledForDeletion = clone $this->collStockOperationTargetStatuses;
                $this->stockOperationTargetStatusesScheduledForDeletion->clear();
            }
            $this->stockOperationTargetStatusesScheduledForDeletion[]= clone $stockOperationTargetStatus;
            $stockOperationTargetStatus->setStockOperation(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this StockOperation is new, it will return
     * an empty collection; or if this StockOperation has previously
     * been saved, it will retrieve related StockOperationTargetStatuses from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in StockOperation.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildStockOperationTargetStatus[] List of ChildStockOperationTargetStatus objects
     */
    public function getStockOperationTargetStatusesJoinOrderStatus($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildStockOperationTargetStatusQuery::create(null, $criteria);
        $query->joinWith('OrderStatus', $joinBehavior);

        return $this->getStockOperationTargetStatuses($query, $con);
    }

    /**
     * Clears out the collStockOperationPaymentModules collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addStockOperationPaymentModules()
     */
    public function clearStockOperationPaymentModules()
    {
        $this->collStockOperationPaymentModules = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collStockOperationPaymentModules collection loaded partially.
     */
    public function resetPartialStockOperationPaymentModules($v = true)
    {
        $this->collStockOperationPaymentModulesPartial = $v;
    }

    /**
     * Initializes the collStockOperationPaymentModules collection.
     *
     * By default this just sets the collStockOperationPaymentModules collection to an empty array (like clearcollStockOperationPaymentModules());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initStockOperationPaymentModules($overrideExisting = true)
    {
        if (null !== $this->collStockOperationPaymentModules && !$overrideExisting) {
            return;
        }
        $this->collStockOperationPaymentModules = new ObjectCollection();
        $this->collStockOperationPaymentModules->setModel('\StockManager\Model\StockOperationPaymentModule');
    }

    /**
     * Gets an array of ChildStockOperationPaymentModule objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildStockOperation is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildStockOperationPaymentModule[] List of ChildStockOperationPaymentModule objects
     * @throws PropelException
     */
    public function getStockOperationPaymentModules($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collStockOperationPaymentModulesPartial && !$this->isNew();
        if (null === $this->collStockOperationPaymentModules || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collStockOperationPaymentModules) {
                // return empty collection
                $this->initStockOperationPaymentModules();
            } else {
                $collStockOperationPaymentModules = ChildStockOperationPaymentModuleQuery::create(null, $criteria)
                    ->filterByStockOperation($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collStockOperationPaymentModulesPartial && count($collStockOperationPaymentModules)) {
                        $this->initStockOperationPaymentModules(false);

                        foreach ($collStockOperationPaymentModules as $obj) {
                            if (false == $this->collStockOperationPaymentModules->contains($obj)) {
                                $this->collStockOperationPaymentModules->append($obj);
                            }
                        }

                        $this->collStockOperationPaymentModulesPartial = true;
                    }

                    reset($collStockOperationPaymentModules);

                    return $collStockOperationPaymentModules;
                }

                if ($partial && $this->collStockOperationPaymentModules) {
                    foreach ($this->collStockOperationPaymentModules as $obj) {
                        if ($obj->isNew()) {
                            $collStockOperationPaymentModules[] = $obj;
                        }
                    }
                }

                $this->collStockOperationPaymentModules = $collStockOperationPaymentModules;
                $this->collStockOperationPaymentModulesPartial = false;
            }
        }

        return $this->collStockOperationPaymentModules;
    }

    /**
     * Sets a collection of StockOperationPaymentModule objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $stockOperationPaymentModules A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildStockOperation The current object (for fluent API support)
     */
    public function setStockOperationPaymentModules(Collection $stockOperationPaymentModules, ConnectionInterface $con = null)
    {
        $stockOperationPaymentModulesToDelete = $this->getStockOperationPaymentModules(new Criteria(), $con)->diff($stockOperationPaymentModules);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->stockOperationPaymentModulesScheduledForDeletion = clone $stockOperationPaymentModulesToDelete;

        foreach ($stockOperationPaymentModulesToDelete as $stockOperationPaymentModuleRemoved) {
            $stockOperationPaymentModuleRemoved->setStockOperation(null);
        }

        $this->collStockOperationPaymentModules = null;
        foreach ($stockOperationPaymentModules as $stockOperationPaymentModule) {
            $this->addStockOperationPaymentModule($stockOperationPaymentModule);
        }

        $this->collStockOperationPaymentModules = $stockOperationPaymentModules;
        $this->collStockOperationPaymentModulesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related StockOperationPaymentModule objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related StockOperationPaymentModule objects.
     * @throws PropelException
     */
    public function countStockOperationPaymentModules(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collStockOperationPaymentModulesPartial && !$this->isNew();
        if (null === $this->collStockOperationPaymentModules || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collStockOperationPaymentModules) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getStockOperationPaymentModules());
            }

            $query = ChildStockOperationPaymentModuleQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByStockOperation($this)
                ->count($con);
        }

        return count($this->collStockOperationPaymentModules);
    }

    /**
     * Method called to associate a ChildStockOperationPaymentModule object to this object
     * through the ChildStockOperationPaymentModule foreign key attribute.
     *
     * @param    ChildStockOperationPaymentModule $l ChildStockOperationPaymentModule
     * @return   \StockManager\Model\StockOperation The current object (for fluent API support)
     */
    public function addStockOperationPaymentModule(ChildStockOperationPaymentModule $l)
    {
        if ($this->collStockOperationPaymentModules === null) {
            $this->initStockOperationPaymentModules();
            $this->collStockOperationPaymentModulesPartial = true;
        }

        if (!in_array($l, $this->collStockOperationPaymentModules->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddStockOperationPaymentModule($l);
        }

        return $this;
    }

    /**
     * @param StockOperationPaymentModule $stockOperationPaymentModule The stockOperationPaymentModule object to add.
     */
    protected function doAddStockOperationPaymentModule($stockOperationPaymentModule)
    {
        $this->collStockOperationPaymentModules[]= $stockOperationPaymentModule;
        $stockOperationPaymentModule->setStockOperation($this);
    }

    /**
     * @param  StockOperationPaymentModule $stockOperationPaymentModule The stockOperationPaymentModule object to remove.
     * @return ChildStockOperation The current object (for fluent API support)
     */
    public function removeStockOperationPaymentModule($stockOperationPaymentModule)
    {
        if ($this->getStockOperationPaymentModules()->contains($stockOperationPaymentModule)) {
            $this->collStockOperationPaymentModules->remove($this->collStockOperationPaymentModules->search($stockOperationPaymentModule));
            if (null === $this->stockOperationPaymentModulesScheduledForDeletion) {
                $this->stockOperationPaymentModulesScheduledForDeletion = clone $this->collStockOperationPaymentModules;
                $this->stockOperationPaymentModulesScheduledForDeletion->clear();
            }
            $this->stockOperationPaymentModulesScheduledForDeletion[]= clone $stockOperationPaymentModule;
            $stockOperationPaymentModule->setStockOperation(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this StockOperation is new, it will return
     * an empty collection; or if this StockOperation has previously
     * been saved, it will retrieve related StockOperationPaymentModules from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in StockOperation.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildStockOperationPaymentModule[] List of ChildStockOperationPaymentModule objects
     */
    public function getStockOperationPaymentModulesJoinModule($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildStockOperationPaymentModuleQuery::create(null, $criteria);
        $query->joinWith('Module', $joinBehavior);

        return $this->getStockOperationPaymentModules($query, $con);
    }

    /**
     * Clears out the collStockOperationDeliveryModules collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addStockOperationDeliveryModules()
     */
    public function clearStockOperationDeliveryModules()
    {
        $this->collStockOperationDeliveryModules = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collStockOperationDeliveryModules collection loaded partially.
     */
    public function resetPartialStockOperationDeliveryModules($v = true)
    {
        $this->collStockOperationDeliveryModulesPartial = $v;
    }

    /**
     * Initializes the collStockOperationDeliveryModules collection.
     *
     * By default this just sets the collStockOperationDeliveryModules collection to an empty array (like clearcollStockOperationDeliveryModules());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initStockOperationDeliveryModules($overrideExisting = true)
    {
        if (null !== $this->collStockOperationDeliveryModules && !$overrideExisting) {
            return;
        }
        $this->collStockOperationDeliveryModules = new ObjectCollection();
        $this->collStockOperationDeliveryModules->setModel('\StockManager\Model\StockOperationDeliveryModule');
    }

    /**
     * Gets an array of ChildStockOperationDeliveryModule objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildStockOperation is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildStockOperationDeliveryModule[] List of ChildStockOperationDeliveryModule objects
     * @throws PropelException
     */
    public function getStockOperationDeliveryModules($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collStockOperationDeliveryModulesPartial && !$this->isNew();
        if (null === $this->collStockOperationDeliveryModules || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collStockOperationDeliveryModules) {
                // return empty collection
                $this->initStockOperationDeliveryModules();
            } else {
                $collStockOperationDeliveryModules = ChildStockOperationDeliveryModuleQuery::create(null, $criteria)
                    ->filterByStockOperation($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collStockOperationDeliveryModulesPartial && count($collStockOperationDeliveryModules)) {
                        $this->initStockOperationDeliveryModules(false);

                        foreach ($collStockOperationDeliveryModules as $obj) {
                            if (false == $this->collStockOperationDeliveryModules->contains($obj)) {
                                $this->collStockOperationDeliveryModules->append($obj);
                            }
                        }

                        $this->collStockOperationDeliveryModulesPartial = true;
                    }

                    reset($collStockOperationDeliveryModules);

                    return $collStockOperationDeliveryModules;
                }

                if ($partial && $this->collStockOperationDeliveryModules) {
                    foreach ($this->collStockOperationDeliveryModules as $obj) {
                        if ($obj->isNew()) {
                            $collStockOperationDeliveryModules[] = $obj;
                        }
                    }
                }

                $this->collStockOperationDeliveryModules = $collStockOperationDeliveryModules;
                $this->collStockOperationDeliveryModulesPartial = false;
            }
        }

        return $this->collStockOperationDeliveryModules;
    }

    /**
     * Sets a collection of StockOperationDeliveryModule objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $stockOperationDeliveryModules A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildStockOperation The current object (for fluent API support)
     */
    public function setStockOperationDeliveryModules(Collection $stockOperationDeliveryModules, ConnectionInterface $con = null)
    {
        $stockOperationDeliveryModulesToDelete = $this->getStockOperationDeliveryModules(new Criteria(), $con)->diff($stockOperationDeliveryModules);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->stockOperationDeliveryModulesScheduledForDeletion = clone $stockOperationDeliveryModulesToDelete;

        foreach ($stockOperationDeliveryModulesToDelete as $stockOperationDeliveryModuleRemoved) {
            $stockOperationDeliveryModuleRemoved->setStockOperation(null);
        }

        $this->collStockOperationDeliveryModules = null;
        foreach ($stockOperationDeliveryModules as $stockOperationDeliveryModule) {
            $this->addStockOperationDeliveryModule($stockOperationDeliveryModule);
        }

        $this->collStockOperationDeliveryModules = $stockOperationDeliveryModules;
        $this->collStockOperationDeliveryModulesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related StockOperationDeliveryModule objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related StockOperationDeliveryModule objects.
     * @throws PropelException
     */
    public function countStockOperationDeliveryModules(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collStockOperationDeliveryModulesPartial && !$this->isNew();
        if (null === $this->collStockOperationDeliveryModules || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collStockOperationDeliveryModules) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getStockOperationDeliveryModules());
            }

            $query = ChildStockOperationDeliveryModuleQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByStockOperation($this)
                ->count($con);
        }

        return count($this->collStockOperationDeliveryModules);
    }

    /**
     * Method called to associate a ChildStockOperationDeliveryModule object to this object
     * through the ChildStockOperationDeliveryModule foreign key attribute.
     *
     * @param    ChildStockOperationDeliveryModule $l ChildStockOperationDeliveryModule
     * @return   \StockManager\Model\StockOperation The current object (for fluent API support)
     */
    public function addStockOperationDeliveryModule(ChildStockOperationDeliveryModule $l)
    {
        if ($this->collStockOperationDeliveryModules === null) {
            $this->initStockOperationDeliveryModules();
            $this->collStockOperationDeliveryModulesPartial = true;
        }

        if (!in_array($l, $this->collStockOperationDeliveryModules->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddStockOperationDeliveryModule($l);
        }

        return $this;
    }

    /**
     * @param StockOperationDeliveryModule $stockOperationDeliveryModule The stockOperationDeliveryModule object to add.
     */
    protected function doAddStockOperationDeliveryModule($stockOperationDeliveryModule)
    {
        $this->collStockOperationDeliveryModules[]= $stockOperationDeliveryModule;
        $stockOperationDeliveryModule->setStockOperation($this);
    }

    /**
     * @param  StockOperationDeliveryModule $stockOperationDeliveryModule The stockOperationDeliveryModule object to remove.
     * @return ChildStockOperation The current object (for fluent API support)
     */
    public function removeStockOperationDeliveryModule($stockOperationDeliveryModule)
    {
        if ($this->getStockOperationDeliveryModules()->contains($stockOperationDeliveryModule)) {
            $this->collStockOperationDeliveryModules->remove($this->collStockOperationDeliveryModules->search($stockOperationDeliveryModule));
            if (null === $this->stockOperationDeliveryModulesScheduledForDeletion) {
                $this->stockOperationDeliveryModulesScheduledForDeletion = clone $this->collStockOperationDeliveryModules;
                $this->stockOperationDeliveryModulesScheduledForDeletion->clear();
            }
            $this->stockOperationDeliveryModulesScheduledForDeletion[]= clone $stockOperationDeliveryModule;
            $stockOperationDeliveryModule->setStockOperation(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this StockOperation is new, it will return
     * an empty collection; or if this StockOperation has previously
     * been saved, it will retrieve related StockOperationDeliveryModules from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in StockOperation.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildStockOperationDeliveryModule[] List of ChildStockOperationDeliveryModule objects
     */
    public function getStockOperationDeliveryModulesJoinModule($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildStockOperationDeliveryModuleQuery::create(null, $criteria);
        $query->joinWith('Module', $joinBehavior);

        return $this->getStockOperationDeliveryModules($query, $con);
    }

    /**
     * Clears the current object and sets all attributes to their default values
     */
    public function clear()
    {
        $this->id = null;
        $this->operation = null;
        $this->created_at = null;
        $this->updated_at = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references to other model objects or collections of model objects.
     *
     * This method is a user-space workaround for PHP's inability to garbage collect
     * objects with circular references (even in PHP 5.3). This is currently necessary
     * when using Propel in certain daemon or large-volume/high-memory operations.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
            if ($this->collStockOperationSourceStatuses) {
                foreach ($this->collStockOperationSourceStatuses as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collStockOperationTargetStatuses) {
                foreach ($this->collStockOperationTargetStatuses as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collStockOperationPaymentModules) {
                foreach ($this->collStockOperationPaymentModules as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collStockOperationDeliveryModules) {
                foreach ($this->collStockOperationDeliveryModules as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collStockOperationSourceStatuses = null;
        $this->collStockOperationTargetStatuses = null;
        $this->collStockOperationPaymentModules = null;
        $this->collStockOperationDeliveryModules = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(StockOperationTableMap::DEFAULT_STRING_FORMAT);
    }

    // timestampable behavior

    /**
     * Mark the current object so that the update date doesn't get updated during next save
     *
     * @return     ChildStockOperation The current object (for fluent API support)
     */
    public function keepUpdateDateUnchanged()
    {
        $this->modifiedColumns[StockOperationTableMap::UPDATED_AT] = true;

        return $this;
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {

    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}
