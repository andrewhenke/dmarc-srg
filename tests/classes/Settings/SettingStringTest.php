<?php

namespace Liuch\DmarcSrg;

use Liuch\DmarcSrg\Users\AdminUser;
use Liuch\DmarcSrg\Settings\SettingsList;
use Liuch\DmarcSrg\Settings\SettingString;
use Liuch\DmarcSrg\Exception\SoftException;
use Liuch\DmarcSrg\Database\DatabaseController;

class SettingStringTest extends \PHPUnit\Framework\TestCase
{
    public function testCreatingWithCorrectValue(): void
    {
        $this->assertSame(
            'someValue',
            (new SettingString([
                'name'  => 'version',
                'value' => 'someValue'
            ], false, $this->getCoreWithDatabaseNever()))->value()
        );
    }

    public function testCreatingWithIncorrectValue(): void
    {
        $this->expectException(SoftException::class);
        $this->expectExceptionMessage('Wrong setting data');
        (new SettingString([
            'name'  => 'version',
            'value' => 111
        ], false, $this->getCoreWithDatabaseNever()))->value();
    }

    public function testCreatingWithIncorrectValueWithIgnoring(): void
    {
        $this->assertSame(
            SettingsList::$schema['version']['default'],
            (new SettingString([
                'name'  => 'version',
                'value' => 111
            ], true, $this->getCoreWithDatabaseNever()))->value()
        );
    }

    public function testGettingValueByName(): void
    {
        $user = new AdminUser($this->getCore());
        $this->assertSame('stringValue', (new SettingString(
            'version',
            false,
            $this->getCoreWithDatabaseOnce('value', 'version', 'stringValue', $user)
        ))->value());
    }

    private function getCore(): object
    {
        return $this->getMockBuilder(Core::class)
                    ->disableOriginalConstructor()
                    ->setMethods([ 'user', 'database' ])
                    ->getMock();
    }

    private function getCoreWithDatabaseNever(): object
    {
        $core = $this->getCore();
        $core->expects($this->never())->method('database');
        return $core;
    }

    private function getCoreWithDatabaseOnce(string $method, $parameter, $value, $user): object
    {
        $mapper = $this->getMockBuilder(StdClass::class)
                       ->disableOriginalConstructor()
                       ->setMethods([ $method ])
                       ->getMock();
        $mapper->expects($this->once())
               ->method($method)
               ->with($this->equalTo($parameter))
               ->willReturn($value);
        $db = $this->getMockBuilder(DatabaseController::class)
                   ->disableOriginalConstructor()
                   ->setMethods([ 'getMapper' ])
                   ->getMock();
        $db->expects($this->once())
           ->method('getMapper')
           ->with($this->equalTo('setting'))
           ->willReturn($mapper);

        $core = $this->getCore();
        $core->expects($this->once())->method('user')->willReturn($user);
        $core->expects($this->once())->method('database')->willReturn($db);
        return $core;
    }
}
