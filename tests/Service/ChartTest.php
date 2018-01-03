<?php

namespace MiaoxingTest\Chart\Service;

class ChartTest extends \Miaoxing\Plugin\Test\BaseTestCase
{
    /**
     * @dataProvider providerForFilter
     */
    public function testFilter($data, $col, $newData)
    {
        $this->assertEquals($newData, wei()->chart->filter($data, $col));
    }

    public function providerForFilter()
    {
        return [
            [
                range(0, 6),
                7,
                range(0, 6),
            ],
            [
                range(0, 13),
                7,
                [
                    0, 2, 4, 7, 9, 11, 13,
                ],
            ],
            [
                range(0, 100),
                8,
                [
                    0, 14, 29, 43, 57, 71, 86, 100,
                ],
            ],
        ];
    }
}
