<?php

namespace MLocati\Terminal\Test\CommandLine\Arguments;

use MLocati\Terminal\CommandLine\Arguments\Joiner;
use MLocati\Terminal\Test\ArgumentsDumper;
use PHPUnit_Framework_TestCase;

class JoinerTest extends PHPUnit_Framework_TestCase
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

    /**
     * @dataProvider invalidArgumentsProvider
     * @expectedException MLocati\Terminal\Exception\InvalidCommandLineArgumentsException
     *
     * @param string $function
     * @param array $arguments
     */
    public function testInvalidArguments($function, array $arguments)
    {
        $joiner = $this->buildJoiner();
        call_user_func([$joiner, $function], $arguments);
    }

    public function joinCommandLinePosixProvider()
    {
        return array_merge($this->joinCommonProvider(), [
            [["test1'"], "'test1'\'''"],
            [["test1'", 'test2'], "'test1'\''' test2"],
            [['test1\\', 'test2'], "'test1\' test2"],
        ]);
    }

    /**
     * @param string[] $arguments
     * @param string $expectedCommandLine
     *
     * @dataProvider joinCommandLinePosixProvider
     */
    public function testJoinPosix(array $arguments, $expectedCommandLine)
    {
        $joiner = $this->buildJoiner();
        $calculatedCommandLine = $joiner->joinCommandLine_Posix($arguments);
        $this->assertSame($expectedCommandLine, $calculatedCommandLine);
    }

    /**
     * @param string[] $arguments
     * @param string $expectedCommandLine
     * @param mixed $argumentsNotInNormalForm
     *
     * @dataProvider joinCommandLinePosixProvider
     * @requires OS Linux|Darwin
     */
    public function testJoinPosixCorrectTestCaseWithProgram(array $arguments, $expectedCommandLine, $argumentsNotInNormalForm = false)
    {
        if ($argumentsNotInNormalForm === false) {
            $a = static::$argumentsDumper->getArgumentsWithProgram($expectedCommandLine);
            $this->assertSame($arguments, $a);
        }
    }

    public function joinCommandLineWindowsProvider()
    {
        return array_merge($this->joinCommonProvider(), [
            [['C:\\Path_to\\main.exe'], 'C:\\Path_to\\main.exe'],
            [['C:\\Path to\\main.exe'], '"C:\\Path to\\main.exe"'],
            // http://daviddeley.com/autohotkey/parameters/parameters.htm#WINCRULESEX
            [['C:\\Path to\\main.exe', 'CallMeIshmael'], '"C:\\Path to\\main.exe" CallMeIshmael'],
            [['C:\\Path to\\main.exe', 'Call Me Ishmael'], '"C:\\Path to\\main.exe" "Call Me Ishmael"'],
            [['C:\\Path to\\main.exe', 'CallMe"Ishmael'], '"C:\\Path to\\main.exe" "CallMe\\"Ishmael"'],
            [['C:\\Path to\\main.exe', 'Call Me Ishmael\\'], '"C:\\Path to\\main.exe" "Call Me Ishmael\\\\"'],
            [['C:\\Path to\\main.exe', 'CallMe\\"Ishmael'], '"C:\\Path to\\main.exe" "CallMe\\\\\\"Ishmael"'],
            [['C:\\Path to\\main.exe', 'a\\\\\\b'], '"C:\\Path to\\main.exe" a\\\\\\b'],
            [['C:\\Path to\\main.exe', '"Call Me Ishmael"'], '"C:\\Path to\\main.exe" "\\"Call Me Ishmael\\""'],
            [['C:\\Path to\\main.exe', 'C:\\TEST A\\'], '"C:\\Path to\\main.exe" "C:\\TEST A\\\\"'],
            [['C:\\Path to\\main.exe', '"C:\\TEST A\\"'], '"C:\\Path to\\main.exe" "\\"C:\\TEST A\\\\\\""'],
            [['C:\\Path to\\main.exe', 'a b c', 'd', 'e'], '"C:\\Path to\\main.exe" "a b c" d e'],
            [['C:\\Path to\\main.exe', 'ab"c', '\\', 'd'], '"C:\\Path to\\main.exe" "ab\\"c" \\ d'],
            [['C:\\Path to\\main.exe', 'a\\\\\\b', 'de fg', 'h'], '"C:\\Path to\\main.exe" a\\\\\\b "de fg" h'],
            [['C:\\Path to\\main.exe', 'a\\"b', 'c', 'd'], '"C:\\Path to\\main.exe" "a\\\\\\"b" c d'],
            [['C:\\Path to\\main.exe', 'a\\\\b c', 'd', 'e'], '"C:\\Path to\\main.exe" "a\\\\b c" d e'],
            [['C:\\Path to\\main.exe', 'a b c"'], '"C:\\Path to\\main.exe" "a b c\\""'],
            [['C:\\Path to\\main.exe', '"CallMeIshmael"', 'b', 'c'], '"C:\\Path to\\main.exe" "\\"CallMeIshmael\\"" b c'],
            [['C:\\Path to\\main.exe', '"Call', 'Me', 'Ishmael"'], '"C:\\Path to\\main.exe" "\\"Call" Me "Ishmael\\""'],
            [['C:\\Path to\\main.exe', '"Call Me Ishmael"', 'b', 'c'], '"C:\\Path to\\main.exe" "\\"Call Me Ishmael\\"" b c'],
            [['C:\\Path_to\\main.exe', '>a'], 'C:\\Path_to\\main.exe ^>a'],
            [['C:\\Path_to\\main.exe', '>a <b | c ^ d & d'], 'C:\\Path_to\\main.exe ">a <b | c ^ d & d"'],
            [['C:\\Path_to\\main.exe', '>a', '<b', '|', 'c', '^', 'd', '&', 'd'], 'C:\\Path_to\\main.exe ^>a ^<b ^| c ^^ d ^& d'],
        ]);
    }

    /**
     * @dataProvider joinCommandLineWindowsProvider
     *
     * @param array $arguments
     * @param mixed $expectedCommandLine
     */
    public function testJoinWindows(array $arguments, $expectedCommandLine)
    {
        $joiner = $this->buildJoiner();
        $calculatedCommandLine = $joiner->joinCommandLine_Windows($arguments);
        $this->assertSame($expectedCommandLine, $calculatedCommandLine);
    }

    /**
     * @param string[] $arguments
     * @param string $expectedCommandLine
     * @param mixed $argumentsNotInNormalForm
     *
     * @dataProvider joinCommandLineWindowsProvider
     * @requires OS WIN32|WINNT
     */
    public function testJoinWindowsCorrectTestCaseWithProgram(array $arguments, $expectedCommandLine, $argumentsNotInNormalForm = false)
    {
        if ($argumentsNotInNormalForm === false) {
            $a = static::$argumentsDumper->getArgumentsWithProgram($expectedCommandLine);
            $this->assertSame($arguments, $a);
        }
    }

    private function buildJoiner()
    {
        $joiner = new Joiner();
        $joiner->setEnableEscapeForWindowsCodepage(false);

        return $joiner;
    }

    private function joinCommonProvider()
    {
        return [
            [['program'], 'program'],
            [['program', 'a'], 'program a'],
            [['program', 'test'], 'program test'],
            [['program', 'a'], 'program a'],
            [['program', 'test'], 'program test'],
            [['program', 'a', 'b'], 'program a b'],
            [['program', 'test1', 'test2'], 'program test1 test2'],
        ];
    }
}
