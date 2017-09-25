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
        return [
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
            [['ùtf-8']],
        ];
    }

    public function splitProvider()
    {
        $programPath = self::getArgumentsDumper()->getProgramPath();
        $result = [];
        foreach ([
            ['<ProgramPath>'],
            ['<ProgramPath>    '],
            ['    <ProgramPath>'],
            ['    <ProgramPath>    '],
            ['<ProgramPath> a'],
            ['<ProgramPath> test'],
            ['<ProgramPath> a   '],
            ['<ProgramPath>    a'],
            ['<ProgramPath>    a   '],
            ['<ProgramPath> test   '],
            ['<ProgramPath>    test'],
            ['<ProgramPath>    test   '],
            ['<ProgramPath> a    b'],
            ['<ProgramPath>    a    b'],
            ['<ProgramPath>    a    b   '],
            ['<ProgramPath> test1    test2'],
            ['<ProgramPath>    test1    test2'],
            ['<ProgramPath>    test1    test2   '],
            ['<ProgramPath> "test"'],
            ['<ProgramPath> "a"'],
            ['<ProgramPath> "a" b'],
            ['<ProgramPath> "test 1" test2'],
            ['<ProgramPath> "a " b'],
            ['<ProgramPath> "test 1 " test2'],
            ['<ProgramPath> "a"b'],
            ['<ProgramPath> "test1"test2'],
            ['<ProgramPath> "a" b'],
            ['<ProgramPath> "test1" test2'],
            ["<ProgramPath> 'a'b"],
            ["<ProgramPath> 'test1'test2"],
            ["<ProgramPath> 'a' b"],
            ["<ProgramPath> 'test1' test2"],
            ['<ProgramPath> a\\ b'],
            ['<ProgramPath> test1\\ test2'],
            ["<ProgramPath> 'test1'\\'''"],
            ["<ProgramPath> 'test1'\\''' test2"],
            ['<ProgramPath> test1\\\\ test2'],
            // http://daviddeley.com/autohotkey/parameters/parameters.htm#WINCRULESEX
            ['<ProgramPath> CallMeIshmael'],
            ['<ProgramPath> "Call Me Ishmael"'],
            ['<ProgramPath> Cal"l Me I"shmael'],
            ['<ProgramPath> CallMe\\"Ishmael'],
            ['<ProgramPath> "CallMe\\"Ishmael"'],
            ['<ProgramPath> "Call Me Ishmael\\\\"'],
            ['<ProgramPath> "CallMe\\\\\\"Ishmael"', '^WIN.*'],
            ['<ProgramPath> a\\\\b'],
            ['<ProgramPath> a\\\\\\b', '^WIN.*'],
            ['<ProgramPath> "a\\\\b"', '^WIN.*'],
            ['<ProgramPath> "\\"Call Me Ishmael\\""'],
            ['<ProgramPath> "C:\\TEST A\\\\"', '^WIN.*'],
            ['<ProgramPath> "\\"C:\\TEST A\\\\\\""', '^WIN.*'],
            ['<ProgramPath> "a b c"  d  e'],
            ['<ProgramPath> "ab\\"c"  "\\\\"  d'],
            ['<ProgramPath> a\\\\b d"e f"g h'],
            ['<ProgramPath> a\\\\\\b d"e f"g h', '^WIN.*'],
            ['<ProgramPath> a\\\\\\"b c d'],
            ['<ProgramPath> a\\\\\\\\"b c" d e'],
            ['<ProgramPath> "a b c""', '^WIN.*'],
            ['<ProgramPath> """CallMeIshmael"""  b  c'],
            ['<ProgramPath> """Call Me Ishmael"""'],
            ['<ProgramPath> """"Call Me Ishmael"" b c'],
            ['<ProgramPath> """"Call Me Ishmael""""'],
        ] as $args) {
            $args[0] = str_replace('<ProgramPath>', $programPath, $args[0]);
            $result[] = $args;
        }

        return $result;
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
     *
     * @dataProvider joinProvider
     */
    public function testJoin(array $arguments)
    {
        $this->assertSame($arguments, self::getArgumentsDumper()->getArguments($arguments));
    }

    /**
     * @dataProvider splitProvider
     *
     * @param string $programPath
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
        $programPath = self::getArgumentsDumper()->getProgramPath();
        $splitter = new Splitter();
        $parts = $splitter->splitCommandLine($commandLine);
        $this->assertArrayHasKey(0, $parts);
        $this->assertSame($programPath, $parts[0]);
        array_shift($parts);
        $parts = array_values($parts);
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
