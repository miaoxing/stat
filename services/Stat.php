<?php

namespace miaoxing\stat\services;

/**
 * @property \Wei\Db $db
 */
class Stat extends \miaoxing\plugin\BaseService
{
    protected $providers = [
        'db' => 'app.db'
    ];

    /**
     * 增加一条统计记录
     *
     * @param string $table
     * @param array $data
     * @return $this
     */
    public function log($table, array $data)
    {
        $data['appId'] = $this->app->getId();
        $data['createTime'] = date('Y-m-d H:i:s');
        $data['createDate'] = substr($data['createTime'], 0, 10);

        $this->db->insert($table, $data);

        return $this;
    }

    /**
     * 创建查询,用于统计的第一步
     *
     * @param string $serviceName
     * @param string $startDate
     * @param string $endDate
     * @return \miaoxing\plugin\BaseModel
     */
    public function createQuery($serviceName, $startDate, $endDate)
    {
        /** @var \miaoxing\plugin\BaseModel $records */
        $records = wei()->get($serviceName);
        $records->select('COUNT(1) AS count, COUNT(DISTINCT(userId)) as user, createDate, action')
            ->where('createDate BETWEEN ? AND ?', [$startDate, $endDate])
            ->groupBy('createDate, action');

        // 附加统计的字段,如领取卡券的卡券编号,金额,来源
        if ($statFields = $records->getOption('statFields')) {
            $records->addSelect($statFields)
                ->addGroupBy($statFields);
        }

        return $records;
    }

    /**
     *  格式化数据,用于统计的第二步
     *
     * 包含两个操作
     * 1. 按日期和统计的字段做索引
     * 2. 操作的人数次数从横列转纵列
     *
     * @param string $serviceName
     * @param array $logs 从操作记录表读出的数据
     * @return array
     */
    public function format($serviceName, array $logs)
    {
        /** @var \miaoxing\plugin\BaseModel $records */
        $records = wei()->get($serviceName);

        $actions = $records->getOption('statActions');
        $statFields = $records->getOption('statFields');

        // 附加创建日期用于生成唯一的索引名称
        array_unshift($statFields, 'createDate');

        $data = [];
        foreach ($logs as $row) {
            // 生成索引
            $indexes = $this->getArrayValuesByKeys($row, $statFields);
            $index = implode('|', $indexes);
            if (!isset($data[$index])) {
                $data[$index] = $indexes;
            }

            // 合并操作数据
            $actonField = $actions[$row['action']];
            $data[$index][$actonField . 'Count'] = (int)$row['count'];
            $data[$index][$actonField . 'User'] = (int)$row['user'];
        }
        return $data;
    }

    /**
     * 记录到统计表中,用于统计的第三步
     *
     * @param string $serviceName
     * @param array $data
     * @param string $table
     * @return $this
     */
    public function save($serviceName, $data, $table)
    {
        /** @var \miaoxing\plugin\BaseModel $records */
        $records = wei()->get($serviceName);

        $actions = $records->getOption('statActions');
        $statFields = $records->getOption('statFields');
        $statTotal = $records->getOption('statTotal');

        foreach ($data as $row) {
            $date = $row['createDate'];

            $values = $this->getArrayValuesByKeys($row, $statFields);
            $stat = $this->db($table)->findOrInit(['statDate' => $date] + $values);
            $stat->fromArray($row);

            if ($statTotal) {
                $prevStat = $this->db($table)
                    ->desc('statDate')
                    ->where('statDate < ?', $date)
                    ->findOrInit($values);

                foreach ($actions as $action) {
                    $field = 'total' . ucfirst($action);
                    $stat[$field . 'Count'] = $prevStat[$field . 'Count'] + $stat[$action . 'Count'];
                    $stat[$field . 'User'] = $prevStat[$field . 'User'] + $stat[$action . 'User'];
                }
            }

            $stat->save();
        }

        return $this;
    }

    /**
     * 获取数组中指定字段的值
     *
     * @param array $array
     * @param array $keys
     * @return array
     */
    protected function getArrayValuesByKeys($array, $keys)
    {
        return array_intersect_key($array, array_flip($keys));
    }
}
