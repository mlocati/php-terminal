<?php

namespace MLocati\Terminal\Test\CommandLine\Arguments;

use MLocati\Terminal\CommandLine\Arguments\Splitter;
use MLocati\Terminal\Test\ArgumentsDumper;
use PHPUnit_Framework_TestCase;

class SplitterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ArgumentsDumper
     */
    private static $argumentsDumper;

    public static function setupBeforeClass()
    {
        self::$argumentsDumper = new ArgumentsDumper();
    }

    public function invalidArgumentsProvider()
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

    /**
     * @dataProvider invalidArgumentsProvider
     * @expectedException MLocati\Terminal\Exception\InvalidCommandLineException
     *
     * @param string $function
     * @param mixed $commandLine
     */
    public function testInvalidArguments($function, $commandLine)
    {
        $splitter = new Splitter();
        call_user_func([$splitter, $function], $commandLine);
    }

    public function mismatchingQuotesProvider()
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

    /**
     * @dataProvider mismatchingQuotesProvider
     * @expectedException MLocati\Terminal\Exception\QuotesMismatchException
     *
     * @param string $function
     * @param string $commandLine
     */
    public function testMismatchingQuotes($function, $commandLine)
    {
        $splitter = new Splitter();
        call_user_func([$splitter, $function], $commandLine);
    }

    public function splitCommandLinePosixProvider()
    {
        return array_merge($this->splitCommonProvider(), [
            ['"a"b', ['ab']],
            ['"test1"test2', ['test1test2']],
            ['"a" b', ['a', 'b']],
            ['"test1" test2', ['test1', 'test2']],
            ["'a'b", ['ab']],
            ["'test1'test2", ['test1test2']],
            ["'a' b", ['a', 'b']],
            ["'test1' test2", ['test1', 'test2']],
            ['a\\ b', ['a b']],
            ['test1\\ test2', ['test1 test2']],
            ["'test1'\\'''", ["test1'"]],
            ["'test1'\\''' test2", ["test1'", 'test2']],
            ['test1\\\\ test2', ['test1\\', 'test2']],
        ]);
    }

    /**
     * @param string $commandLine
     * @param string[] $expectedArguments
     *
     * @dataProvider splitCommandLinePosixProvider
     */
    public function testSplitPosix($commandLine, array $expectedArguments)
    {
        $splitter = new Splitter();
        $calculatedArguments = $splitter->splitCommandLine_Posix($commandLine);
        $this->assertSame($expectedArguments, $calculatedArguments, "Splitting >{$commandLine}<");
    }

    /**
     * @param string $commandLine
     * @param string[] $expectedArguments
     *
     * @dataProvider splitCommandLinePosixProvider
     * @requires OS Linux|Darwin
     */
    public function testSplitPosixCorrectTestCaseWithProgram($commandLine, array $expectedArguments)
    {
        $a = static::$argumentsDumper->getArgumentsWithProgram($commandLine);
        $this->assertSame($expectedArguments, $a);
    }

    public function splitCommandLineWindowsProvider()
    {
        return array_merge($this->splitCommonProvider(), [
            ['C:\\Path_to\\main.exe', ['C:\\Path_to\\main.exe']],
            ['"C:\\Path to\\main.exe"', ['C:\\Path to\\main.exe']],
            ['"C:\\Path_to\\main.exe', ['C:\\Path_to\\main.exe']],
            ['C:\\Path_to\\main".exe', ['C:\\Path_to\\main.exe']],
            // http://daviddeley.com/autohotkey/parameters/parameters.htm#WINCRULESEX
            ['"C:\\Path to\\main.exe" CallMeIshmael', ['C:\\Path to\\main.exe', 'CallMeIshmael']],
            ['"C:\\Path to\\main.exe" "Call Me Ishmael"', ['C:\\Path to\\main.exe', 'Call Me Ishmael']],
            ['"C:\\Path to\\main.exe" Cal"l Me I"shmael', ['C:\\Path to\\main.exe', 'Call Me Ishmael']],
            ['"C:\\Path to\\main.exe" CallMe\\"Ishmael', ['C:\\Path to\\main.exe', 'CallMe"Ishmael']],
            ['"C:\\Path to\\main.exe" "CallMe\\"Ishmael"', ['C:\\Path to\\main.exe', 'CallMe"Ishmael']],
            ['"C:\\Path to\\main.exe" "Call Me Ishmael\\\\"', ['C:\\Path to\\main.exe', 'Call Me Ishmael\\']],
            ['"C:\\Path to\\main.exe" "CallMe\\\\\\"Ishmael"', ['C:\\Path to\\main.exe', 'CallMe\\"Ishmael']],
            ['"C:\\Path to\\main.exe" a\\\\\\b', ['C:\\Path to\\main.exe', 'a\\\\\\b']],
            ['"C:\\Path to\\main.exe" "a\\\\\\b"', ['C:\\Path to\\main.exe', 'a\\\\\\b']],
            ['"C:\\Path to\\main.exe" "\\"Call Me Ishmael\\""', ['C:\\Path to\\main.exe', '"Call Me Ishmael"']],
            ['"C:\\Path to\\main.exe" "C:\\TEST A\\\\"', ['C:\\Path to\\main.exe', 'C:\\TEST A\\']],
            ['"C:\\Path to\\main.exe" "\\"C:\\TEST A\\\\\\""', ['C:\\Path to\\main.exe', '"C:\\TEST A\\"']],
            ['"C:\\Path to\\main.exe" "a b c"  d  e', ['C:\\Path to\\main.exe', 'a b c', 'd', 'e']],
            ['"C:\\Path to\\main.exe" "ab\\"c"  "\\\\"  d', ['C:\\Path to\\main.exe', 'ab"c', '\\', 'd']],
            ['"C:\\Path to\\main.exe" a\\\\\\b d"e f"g h', ['C:\\Path to\\main.exe', 'a\\\\\\b', 'de fg', 'h']],
            ['"C:\\Path to\\main.exe" a\\\\\\"b c d', ['C:\\Path to\\main.exe', 'a\\"b', 'c', 'd']],
            ['"C:\\Path to\\main.exe" a\\\\\\\\"b c" d e', ['C:\\Path to\\main.exe', 'a\\\\b c', 'd', 'e']],
            ['"C:\\Path to\\main.exe" "a b c""', ['C:\\Path to\\main.exe', 'a b c"']],
            ['"C:\\Path to\\main.exe" """CallMeIshmael"""  b  c', ['C:\\Path to\\main.exe', '"CallMeIshmael"', 'b', 'c']],
            ['"C:\\Path to\\main.exe" """Call Me Ishmael"""', ['C:\\Path to\\main.exe', '"Call Me Ishmael"']],
            ['"C:\\Path to\\main.exe" """"Call Me Ishmael"" b c', ['C:\\Path to\\main.exe', '"Call', 'Me', 'Ishmael', 'b', 'c']],
            ['"C:\\Path to\\main.exe" """"Call Me Ishmael""""', ['C:\\Path to\\main.exe', '"Call', 'Me', 'Ishmael"']],
        ]);
    }

    /**
     * @param string $commandLine
     * @param string[] $expectedArguments
     *
     * @dataProvider splitCommandLineWindowsProvider
     */
    public function testSplitWindows($commandLine, array $expectedArguments)
    {
        $splitter = new Splitter();
        $calculatedArguments = $splitter->splitCommandLine_Windows($commandLine);
        $this->assertSame($expectedArguments, $calculatedArguments, "Splitting >{$commandLine}<");
    }

    /**
     * @param string $commandLine
     * @param string[] $expectedArguments
     *
     * @dataProvider splitCommandLineWindowsProvider
     * @requires OS WIN32|WINNT
     */
    public function testSplitWindowsCorrectTestCaseWithProgram($commandLine, array $expectedArguments)
    {
        $a = static::$argumentsDumper->getArgumentsWithProgram($commandLine);
        $this->assertSame($expectedArguments, $a);
    }

    private function splitCommonProvider()
    {
        return [
            ['program', ['program']],
            ['program    ', ['program']],
            ['    program', ['program']],
            ['    program    ', ['program']],
            ['program a', ['program', 'a']],
            ['program test', ['program', 'test']],
            ['program a   ', ['program', 'a']],
            ['program    a', ['program', 'a']],
            ['program    a   ', ['program', 'a']],
            ['program test   ', ['program', 'test']],
            ['program    test', ['program', 'test']],
            ['program    test   ', ['program', 'test']],
            ['program a    b', ['program', 'a', 'b']],
            ['program    a    b', ['program', 'a', 'b']],
            ['program    a    b   ', ['program', 'a', 'b']],
            ['program test1    test2', ['program', 'test1', 'test2']],
            ['program    test1    test2', ['program', 'test1', 'test2']],
            ['program    test1    test2   ', ['program', 'test1', 'test2']],
            ['program "test"', ['program', 'test']],
            ['program "a"', ['program', 'a']],
            ['program "a" b', ['program', 'a', 'b']],
            ['program "test 1" test2', ['program', 'test 1', 'test2']],
            ['program "a " b', ['program', 'a ', 'b']],
            ['program "test 1 " test2', ['program', 'test 1 ', 'test2']],
        ];
    }
}
