<?php

namespace Tests\TomCan\PublicSuffixList;

use PHPUnit\Framework\TestCase;
use TomCan\PublicSuffixList\AbstractPSL;
use TomCan\PublicSuffixList\PSLInterface;

class ConcreteAbstractPSL extends AbstractPSL
{
    public function __construct()
    {
        $this->lists = [
            'icann' => [
                'com',
                'org',
                'be',
            ],
            'private' => [
                'tom.be',
                'xn--tm-fka.be', // töm.be
                '*.wild.tom.be',
                '!not.wild.tom.be',
            ],
        ];
    }
}

class AbstractPSLTest extends TestCase
{
    private PSLInterface $psl;

    protected function setUp(): void
    {
        $this->psl = new ConcreteAbstractPSL();
    }

    public function testIsTld()
    {
        // exact from icann
        $this->assertTrue($this->psl->isTld('com'));
        $this->assertTrue($this->psl->isTld('org'));
        $this->assertTrue($this->psl->isTld('be'));
        // exact from private
        $this->assertTrue($this->psl->isTld('tom.be'));
        // wildcard
        $this->assertFalse($this->psl->isTld('wild.tom.be'));
        $this->assertTrue($this->psl->isTld('www.wild.tom.be'));
        // explicitly excluded
        $this->assertFalse($this->psl->isTld('not.wild.tom.be'));
        // IDN
        $this->assertTrue($this->psl->isTld('töm.be'));
        $this->assertTrue($this->psl->isTld('xn--tm-fka.be'));
    }

    public function testGetType()
    {
        $this->assertEquals('icann', $this->psl->getType('com'));
        $this->assertEquals('icann', $this->psl->getType('be'));
        $this->assertEquals('private', $this->psl->getType('tom.be'));
        $this->assertEquals('private', $this->psl->getType('www.wild.tom.be'));
        $this->assertNull($this->psl->getType('not.wild.tom.be'));
        $this->assertNull($this->psl->getType('nonexistent'));
        $this->assertNull($this->psl->getType(''));
    }

    public function testGetTldOfDomain()
    {
        $this->assertEquals('com', $this->psl->getTldOfDomain('example.com'));
        $this->assertEquals('be', $this->psl->getTldOfDomain('tc.be'));
        $this->assertEquals('tom.be', $this->psl->getTldOfDomain('tom.be'));
        $this->assertEquals('sub.wild.tom.be', $this->psl->getTldOfDomain('www.sub.wild.tom.be'));
        $this->assertEquals('tom.be', $this->psl->getTldOfDomain('sub.of.not.wild.tom.be'));
        $this->assertEquals('xn--tm-fka.be', $this->psl->getTldOfDomain('sub.of.töm.be'));
        $this->assertEquals('xn--tm-fka.be', $this->psl->getTldOfDomain('sub.of.xn--tm-fka.be'));
        $this->assertNull($this->psl->getTldOfDomain('be.tom'));
        $this->assertNull($this->psl->getTldOfDomain(''));
    }

    public function testGetFullList()
    {
        $expected = [
            'com',
            'org',
            'be',
            'tom.be',
            'xn--tm-fka.be', // töm.be
            '*.wild.tom.be',
            '!not.wild.tom.be',
        ];

        $this->assertEquals($expected, $this->psl->getFullList());
    }

    public function testSanetizeTld()
    {
        $reflection = new \ReflectionClass(ConcreteAbstractPSL::class);
        $method = $reflection->getMethod('sanetizeTld');
        $method->setAccessible(true);

        $this->assertEquals('tom.be', $method->invoke($this->psl, 'TOM.BE'));
        $this->assertEquals('tom.be', $method->invoke($this->psl, 'tom.be '));
        $this->assertEquals('tom.be', $method->invoke($this->psl, 'tom.be.'));
        $this->assertEquals('xn--tm-fka.be', $method->invoke($this->psl, 'töm.be'));
        $this->assertEquals('tom.be', $method->invoke($this->psl, 'tom.be. extra'));
        $this->assertEquals('tom.be', $method->invoke($this->psl, '.tom.be extra'));
    }
}
