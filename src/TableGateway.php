<?php

namespace Phpfox\Db;


class TableGateway
{
    /**
     * @var string
     */
    protected $identity = '';

    /**
     * @var array
     */
    protected $column = [];

    /**
     * @var null
     */
    protected $driver = null;

    /**
     * @var array
     */
    protected $primary = [];

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $class = '\Kendo\Model';

    /**
     * @var array
     */
    protected $defaultValue = [];

    /**
     * @return string|false
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    public function __construct()
    {

    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function insert($data)
    {
        return (new SqlInsert($this->_adapter()))->insert($this->getName(),
            array_intersect_key($data, $this->getColumn()))->execute();
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function insertIgnore($data)
    {
        $sql = (new SqlInsert($this->_adapter()))->insert($this->getName(),
            array_intersect_key($data, $this->getColumn()))
            ->ignoreOnDuplicate(true);

        return $sql->execute();
    }

    /**
     * @return AdapterInterface
     */
    public function _adapter()
    {
        return \app()->db()->getAdapter($this->getDriver());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return \app()->db()->getName($this->name);
    }

    /**
     * @return array
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @return array
     */
    public function getDefault()
    {
        return $this->defaultValue;
    }

    /**
     * @param  array $data
     *
     * @return array (expression, condition)
     */
    public function getCondition($data)
    {

        $primaryData = array_intersect_key($data, $this->getPrimary());

        $expressionArray = [];
        $condition = [];

        foreach ($primaryData as $k => $v) {
            $expressionArray [] = "$k=:$k ";
            $condition [":$k"] = $v;
        }

        $expression = implode(' AND ', $expressionArray);

        return [$expression, $condition];
    }

    /**
     * @return array
     */
    public function getPrimary()
    {
        return $this->primary;
    }

    /**
     * @return array
     */
    public function getColumnNotPrimary()
    {
        return array_diff_key($this->column, $this->primary);
    }

    /**
     * @param array $data
     * @param array $values
     *
     * @return mixed
     */
    public function updateModel($data, $values = null)
    {
        if (empty($values)) {
            $values = $data;
        }

        $values = array_intersect_key($values, $this->getColumnNotPrimary());

        if (empty($values)) {
            return true;
        }

        $query = new SqlUpdate($this->_adapter());

        $query->update($this->getName(), $values);

        foreach ($this->getPrimary() as $column => $type) {
            $query->where("$column=?", $data[$column]);
        }

        return $query->execute();
    }

    /**
     * @param array $values
     *
     * @return SqlUpdate
     */
    public function update($values)
    {
        return (new SqlUpdate($this->_adapter()))->update($this->getName(),
            $values);
    }

    /**
     * @param  array|null $data
     *
     * @return mixed
     */
    public function create($data = null)
    {
        return (new ($this->class)($data, false));
    }

    /**
     * @param string $alias
     *
     * @return SqlSelect
     */
    public function select($alias = null)
    {
        if (null == $alias) {
            $alias = 't1';
        }

        return (new SqlSelect($this->_adapter()))->setModel($this->class)
            ->from($this->getName(), $alias);
    }

    /**
     * @param  array $data
     *
     * @return bool
     */
    public function deleteByModelData($data)
    {
        $sql = $this->delete();

        foreach ($this->getPrimary() as $column => $type) {
            $sql->where("$column=?", $data[$column]);
        }

        return $sql->execute();
    }

    /**
     * @return SqlDelete
     */
    public function delete()
    {
        return (new SqlDelete($this->_adapter()))->from($this->getName());
    }

    /**
     * @param array $value
     *
     * @return array
     */
    public function findByIdList($value)
    {

    }
}