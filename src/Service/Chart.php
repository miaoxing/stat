<?php

namespace Miaoxing\Stat\Service;

use Miaoxing\Plugin\BaseService;

/**
 * 构造图表数据的辅助服务
 */
class Chart extends BaseService
{
    /**
     * 支持余数为0的除法,同时支持显示小数点后位数
     *
     * @param $left
     * @param $right
     * @param null|int $scale
     * @return float|int|string
     */
    public function div($left, $right, $scale = null)
    {
        $result = $right == 0 ? 0 : $left / $right;

        return $scale === null ? $result : $this->toFixed($result, $scale);
    }

    public function percentage($left, $right, $scale = 2)
    {
        return $this->div($left * 100, $right, $scale) . '%';
    }

    /**
     * 转换数字型字符串为数字,以符合highcharts要求
     *
     * @param array $data
     * @return array
     */
    public function convertNumbers($data)
    {
        foreach ($data as &$row) {
            $row = $this->convertRowNumbers($row);
        }

        return $data;
    }

    protected function convertRowNumbers($row)
    {
        foreach ($row as &$value) {
            if (is_numeric($value)) {
                if (wei()->isDecimal($value)) {
                    $value = (float) $this->toFixed($value, 2);
                } else {
                    $value = (int) $value;
                }
            }
        }

        return $row;
    }

    /**
     * 获取二维数组中的某一列,并转换为数字
     *
     * @param array $data
     * @param string $column
     * @return mixed
     */
    public function getColumnValues($data, $column)
    {
        $values = $this->coll->column($data, $column);

        return $this->convertRowNumbers($values);
    }

    /**
     * 转换数字为小数,常用于显示价格,金额
     *
     * @param int|float $var
     * @param int $x
     * @return float
     */
    public function toFixed($var, $x = 2)
    {
        return sprintf('%.' . $x . 'f', $var);
    }

    /**
     * 补齐数组中缺少的日期数据
     *
     * @param array $data 二维数组
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @param array $defaults 默认数据
     * @return array
     */
    public function fillDate(array $data, $startDate, $endDate, array $defaults = [])
    {
        for ($date = $startDate; $date <= $endDate; $date = date('Y-m-d', strtotime($date) + 86400)) {
            if (!isset($data[$date])) {
                $data[$date] = $defaults;
            }
        }
        ksort($data);

        return $data;
    }

    public function filter(array $data, $col = 7)
    {
        $count = count($data);
        if (count($data) < $col) {
            return $data;
        }

        $newData = [];

        // 需要生成的列数
        $newCol = $col - 1;

        // 每列宽度
        $parts = ($count - 1) / $newCol;

        // 最后一列不参与计算
        $last = array_pop($data);

        // 四舍五入计算每列位置
        for ($i = 0; $i < $newCol; ++$i) {
            $newData[$i] = $data[round($i * $parts)];
        }

        // 加入最后一列
        $newData[] = $last;

        return $newData;
    }

    /**
     * 标准化二维数组数据
     *
     * 1. 转换数据为数字
     * 2. 补齐缺少的日期和数据
     * 3. 按日期排序
     *
     * @param array $data
     * @param string $startDate
     * @param string $endDate
     * @param array $defaults
     * @param string $dateKey
     * @return array
     * @deprecated
     */
    public function normalize($data, $startDate, $endDate, $defaults = [], $dateKey = 'statDate')
    {
        // 转换为数字
        $data = $this->convertNumbers($data);

        // 补齐缺少的日期和数据
        $defaults || $defaults = $this->generateDefaultData($data);
        $dates = array_flip($this->coll->column($data, $dateKey));
        for ($date = $startDate; $date <= $endDate; $date = date('Y-m-d', strtotime($date) + 86400)) {
            if (!isset($dates[$date])) {
                // TODO 如果有累积的字段,且中间缺少数据(统计未运行?),需要补上
                $data[] = [$dateKey => $date] + $defaults;
            }
        }

        // 按日期排序
        $data = wei()->coll->orderBy($data, $dateKey, SORT_ASC);

        return $data;
    }

    /**
     * 根据提供的二维数组,生成一份的默认数据
     *
     * @param array $data
     * @return array
     */
    protected function generateDefaultData($data)
    {
        if (!$data) {
            return [];
        }
        $data = current($data);
        foreach ($data as $key => $value) {
            $data[$key] = is_numeric($value) ? 0 : null;
        }

        return $data;
    }
}
