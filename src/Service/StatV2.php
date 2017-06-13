<?php

namespace Miaoxing\Stat\Service;

use miaoxing\plugin\BaseModel;

/**
 * 和原版区别在于字段名使用下划线
 *
 * @property \Wei\Db $db
 * @property \Miaoxing\App\Service\Coll $coll
 */
class StatV2 extends \miaoxing\plugin\BaseService
{
    protected $providers = [
        'db' => 'app.db',
    ];

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
        $records->select('COUNT(1) AS count, COUNT(DISTINCT(user_id)) as user, created_date, action')
            ->where('created_date BETWEEN ? AND ?', [$startDate, $endDate])
            ->groupBy('created_date, action');

        // 附加叠加的字段,如金额
        $sumFields = $records->getOption('statSums');
        if ($sumFields) {
            foreach ($sumFields as $field) {
                $records->addSelect('SUM(' . $field . ') AS ' . $field);
            }
        }

        // 附加统计的字段,如领取卡券的卡券编号,来源
        $statFields = $records->getOption('statFields');
        if ($statFields) {
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
        $statSums = $records->getOption('statSums');

        // 附加创建日期用于生成唯一的索引名称
        array_unshift($statFields, 'created_date');

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
            $data[$index][$actonField . '_count'] = (int) $row['count'];
            $data[$index][$actonField . '_user'] = (int) $row['user'];

            foreach ($statSums as $sum) {
                $data[$index][$actonField . '_' . $sum] = $row[$sum];
            }
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
        $statSums = $records->getOption('statSums');
        $statTotal = $records->getOption('statTotal');

        foreach ($data as $row) {
            $date = $row['created_date'];

            $values = $this->getArrayValuesByKeys($row, $statFields);
            $stat = $this->$table()->findOrInit(['stat_date' => $date] + $values);
            $stat->fromArray($row);

            if ($statTotal) {
                $prevStat = $this->$table()
                    ->desc('stat_date')
                    ->where('stat_date < ?', $date)
                    ->findOrInit($values);

                foreach ($actions as $action) {
                    $field = 'total' . ucfirst($action);
                    $stat[$field . '_count'] = $prevStat[$field . '_count'] + $stat[$action . '_count'];
                    $stat[$field . '_user'] = $prevStat[$field . '_user'] + $stat[$action . 'user'];

                    foreach ($statSums as $sum) {
                        $sumField = $field . ucfirst($sum);
                        $stat[$sumField] = $prevStat[$sumField] + $stat[$action . ucfirst($sum)];
                    }
                }
            }

            $stat->save();
        }

        return $this;
    }

    /**
     * @param string $serviceName
     * @param array $data
     * @param array $defaults
     * @param array $lastTotalRow
     * @param string $startDate
     * @param string $endDate
     * @return array
     * @todo 减少参数
     */
    public function normalize($serviceName, $data, $defaults, $lastTotalRow, $startDate, $endDate)
    {
        $dateKey = 'stat_date';

        /** @var \miaoxing\plugin\BaseModel $records */
        $records = wei()->get($serviceName);

        // 生成累积的字段名称
        $totalFields = $this->getTotalFields($records);

        // 逐个日期补上数据
        $data = $this->coll->indexBy($data, $dateKey);
        for ($date = $startDate; $date <= $endDate; $date = date('Y-m-d', strtotime($date) + 86400)) {
            if (!isset($data[$date])) {
                $lastTotalRow = array_intersect_key($lastTotalRow, $totalFields);
                $data[$date] = $lastTotalRow + [$dateKey => $date] + $defaults;
            }
            $lastTotalRow = array_intersect_key($data[$date], $totalFields);
        }

        // 还原key为数字,并按日期排列
        $data = array_values($data);
        $data = $this->coll->orderBy($data, $dateKey, SORT_ASC);

        return $data;
    }

    protected function getTotalFields(BaseModel $records)
    {
        $statTotal = $records->getOption('statTotal');
        if (!$statTotal) {
            return [];
        }

        $totalFields = [];
        $statSums = $records->getOption('statSums');
        $statActions = $records->getOption('statActions');
        foreach ($statActions as $field) {
            $totalFields[] = 'total' . ucfirst($field) . 'Count';
            $totalFields[] = 'total' . ucfirst($field) . 'User';

            foreach ($statSums as $sum) {
                $totalFields[] = 'total' . ucfirst($field) . ucfirst($sum);
            }
        }

        return array_flip($totalFields);
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
