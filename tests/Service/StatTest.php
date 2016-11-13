<?php

namespace MiaoxingTest\Stat\Stests\Service;

use Miaoxing\Plugin\Test\BaseTestCase;

/**
 * 统计服务
 */
class StatTest extends BaseTestCase
{
    /**
     * 记录一条日志
     */
    public function testLog()
    {
        $db = $this->getServiceMock('db', ['insert']);
        wei()->stat->db = $db;

        $db->expects($this->once())
            ->method('insert')
            ->with('test', $this->logicalAnd(
                $this->arrayHasKey('a'),
                $this->arrayHasKey('appId'),
                $this->arrayHasKey('createTime'),
                $this->arrayHasKey('createDate')
            ));

        wei()->stat->log('test', [
            'a' => 1,
        ]);
    }
}
