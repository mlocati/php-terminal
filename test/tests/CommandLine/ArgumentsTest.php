<?php

namespace MLocati\Terminal\Test\CommandLine;

use MLocati\Terminal\CommandLine\Arguments\Joiner;
use MLocati\Terminal\CommandLine\Arguments\Splitter;
use MLocati\Terminal\Test\ArgumentsDumper;
use PHPUnit_Framework_TestCase;

class ArgumentsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ArgumentsDumper|null
     */
    private static $argumentsDumper;

    public function invalidJoinArgumentsProvider()
    {
        $functions = [
            'joinCommandLine',
            'joinCommandLine_Posix',
            'joinCommandLine_Windows',
        ];
        $argumentsList = [
            [null],
            [false],
            [''],
            [null, 'a'],
            [false, 'a'],
            ['', 'a'],
        ];
        $result = [];
        foreach ($functions as $function) {
            foreach ($argumentsList as $arguments) {
                $result[] = [$function, $arguments];
            }
        }

        return $result;
    }

    public function invalidSplitArgumentsProvider()
    {
        $functions = [
            'splitCommandLine',
            'splitCommandLine_Posix',
            'splitCommandLine_Windows',
        ];
        $commandLines = [
            null,
            123,
            '',
            '   ',
        ];
        $result = [];
        foreach ($functions as $function) {
            foreach ($commandLines as $commandLine) {
                $result[] = [$function, $commandLine];
            }
        }

        return $result;
    }

    public function splitMismatchingQuotesProvider()
    {
        $result = [];
        foreach ([
            'splitCommandLine_Posix' => [
                '"a',
                '"test',
                'a"',
                'test"',
                "'a",
                "'test",
                "a'",
                "test'",
                '"a b',
                '"test1 test2',
                'a" b',
                'test1" test2',
                "'a b",
                "'test1 test2",
                "a' b",
                "test1' test2",
            ],
            'splitCommandLine_Windows' => [
            ],
        ] as $function => $commandLines) {
            foreach ($commandLines as $commandLine) {
                $result[] = [$function, $commandLine];
            }
        }

        return $result;
    }

    public function joinProvider()
    {
        return [/*
            [[]],
            [['a']],
            [['test']],
            [['a']],
            [['test']],
            [['a', 'b']],
            [['test1', 'test2']],
            [["test1'"]],
            [["test1'", 'test2']],
            [['test1\\', 'test2']],
            [['CallMeIshmael']],
            [['Call Me Ishmael']],
            [['CallMe"Ishmael']],
            [['Call Me Ishmael\\']],
            [['CallMe\\"Ishmael']],
            [['a\\\\\\b']],
            [['"Call Me Ishmael"']],
            [['C:\\TEST A\\']],
            [['"C:\\TEST A\\"']],
            [['a b c', 'd', 'e']],
            [['ab"c', '\\', 'd']],
            [['a\\\\\\b', 'de fg', 'h']],
            [['a\\"b', 'c', 'd']],
            [['a\\\\b c', 'd', 'e']],
            [['a b c"']],
            // http://daviddeley.com/autohotkey/parameters/parameters.htm#WINCRULESEX
            [['"CallMeIshmael"', 'b', 'c']],
            [['"Call', 'Me', 'Ishmael"']],
            [['"Call Me Ishmael"', 'b', 'c']],
            [['>a']],
            [['>a <b | c ^ d & d']],
            [['>a', '<b', '|', 'c', '^', 'd', '&', 'd']],
            */
            [['ùtf-8'], [65001, 850]],
            [['àtf-8'], [65001, 437, 850]],
        ];
    }

    public function splitProvider()
    {
        return [
            [''],
            ['    '],
            ['    '],
            ['        '],
            ['a'],
            ['test'],
            ['a   '],
            ['   a'],
            ['   a   '],
            ['test   '],
            ['   test'],
            ['   test   '],
            ['a    b'],
            ['   a    b'],
            ['   a    b   '],
            ['test1    test2'],
            ['   test1    test2'],
            ['   test1    test2   '],
            ['"test"'],
            ['"a"'],
            ['"a" b'],
            ['"test 1" test2'],
            ['"a " b'],
            ['"test 1 " test2'],
            ['"a"b'],
            ['"test1"test2'],
            ['"a" b'],
            ['"test1" test2'],
            ["'a'b"],
            ["'test1'test2"],
            ["'a' b"],
            ["'test1' test2"],
            ['a\\ b'],
            ['test1\\ test2'],
            ["'test1'\\'''"],
            ["'test1'\\''' test2"],
            ['test1\\\\ test2'],
            // http://daviddeley.com/autohotkey/parameters/parameters.htm#WINCRULESEX
            ['CallMeIshmael'],
            ['"Call Me Ishmael"'],
            ['Cal"l Me I"shmael'],
            ['CallMe\\"Ishmael'],
            ['"CallMe\\"Ishmael"'],
            ['"Call Me Ishmael\\\\"'],
            ['"CallMe\\\\\\"Ishmael"', '^WIN.*'],
            ['a\\\\b'],
            ['a\\\\\\b', '^WIN.*'],
            ['"a\\\\b"', '^WIN.*'],
            ['"\\"Call Me Ishmael\\""'],
            ['"C:\\TEST A\\\\"', '^WIN.*'],
            ['"\\"C:\\TEST A\\\\\\""', '^WIN.*'],
            ['"a b c"  d  e'],
            ['"ab\\"c"  "\\\\"  d'],
            ['a\\\\b d"e f"g h'],
            ['a\\\\\\b d"e f"g h', '^WIN.*'],
            ['a\\\\\\"b c d'],
            ['a\\\\\\\\"b c" d e'],
            ['"a b c""', '^WIN.*'],
            ['"""CallMeIshmael"""  b  c'],
            ['"""Call Me Ishmael"""'],
            ['""""Call Me Ishmael"" b c'],
            ['""""Call Me Ishmael""""'],
        ];
    }

    /**
     * @dataProvider invalidJoinArgumentsProvider
     * @expectedException MLocati\Terminal\Exception\InvalidCommandLineArgumentsException
     *
     * @param string $function
     * @param array $arguments
     */
    public function testInvalidArguments($function, array $arguments)
    {
        $joiner = new Joiner();
        call_user_func([$joiner, $function], $arguments);
    }

    /**
     * @dataProvider invalidSplitArgumentsProvider
     * @expectedException MLocati\Terminal\Exception\InvalidCommandLineException
     *
     * @param string $function
     * @param mixed $commandLine
     */
    public function testInvalidSplitArguments($function, $commandLine)
    {
        $splitter = new Splitter();
        call_user_func([$splitter, $function], $commandLine);
    }

    /**
     * @dataProvider splitMismatchingQuotesProvider
     *
     * @param string $function
     * @param string $commandLine
     *
     * @expectedException MLocati\Terminal\Exception\QuotesMismatchException
     */
    public function testSplitMismatchingQuotes($function, $commandLine)
    {
        $splitter = new Splitter();
        call_user_func([$splitter, $function], $commandLine);
    }

    /**
     * @param string[] $arguments
     * @param int[] $validWindowsCodepages
     *
     * @dataProvider joinProvider
     */
    public function testJoin(array $arguments, array $validWindowsCodepages = [])
    {
        if (DIRECTORY_SEPARATOR === '\\' && !empty($validWindowsCodepages)) {
            $cp = self::getArgumentsDumper()->getWindowsCodepage();
            if (!in_array($cp, $validWindowsCodepages, true)) {
                $this->markTestSkipped('Under Windows this test requires codepage ' . implode(' or ', $validWindowsCodepages) . ", but the current code page is {$cp}");
            }
        }
        $this->assertSame($arguments, self::getArgumentsDumper()->getArguments($arguments));
    }

    /**
     * @dataProvider splitProvider
     *
     * @param string $commandLine
     * @param string|null $requiredOS
     */
    public function testSplit($commandLine, $requiredOS = null)
    {
        if ($requiredOS !== null) {
            $currentOS = PHP_OS;
            if (preg_match("/{$requiredOS}/i", $currentOS) === 0) {
                $this->markTestSkipped("Current OS: {$currentOS}, required OS: {$requiredOS}");
            }
        }
        $splitter = new Splitter();
        $parts = $splitter->splitCommandLineArguments($commandLine);
        $this->assertSame($parts, self::getArgumentsDumper()->runCommand($commandLine));
    }

    /**
     * @return ArgumentsDumper
     */
    private static function getArgumentsDumper()
    {
        if (self::$argumentsDumper === null) {
            self::$argumentsDumper = new ArgumentsDumper();
        }

        return self::$argumentsDumper;
    }
}
