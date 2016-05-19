<?php

require_once "../PHPUnit/autoload.php";
require_once "./Reversi.php";

class ReversiTest extends PHPUnit_Framework_TestCase
{
    private static $_reversi = null;

    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('ReversiTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * インスタンス生成
     */
    protected function setUp()
    {
        self::$_reversi = new Reversi();
    }

    /**
     * 初期状態
     */
    public function test_mainStartGame()
    {
        $isEnd        = false;
        $move         = 1;
        $myStoneColor = 1;
        $blackCount   = 2;
        $whiteCount   = 2;
        $blackField   = '3@4_4@3';
        $whiteField   = '3@3_4@4';
        $field        = [
            0 => [0,0,0,0,0,0,0,0],
            1 => [0,0,0,0,0,0,0,0],
            2 => [0,0,0,0,0,0,0,0],
            3 => [0,0,0,2,1,0,0,0],
            4 => [0,0,0,1,2,0,0,0],
            5 => [0,0,0,0,0,0,0,0],
            6 => [0,0,0,0,0,0,0,0],
            7 => [0,0,0,0,0,0,0,0],
        ];
        $blackSetStonePositions = [
            2 => [3 => true],
            3 => [2 => true],
            4 => [5 => true],
            5 => [4 => true],
        ];
        $whiteSetStonePositions = [
            2 => [4 => true],
            3 => [5 => true],
            4 => [2 => true],
            5 => [3 => true],
        ];

        // mainメソッド呼び出し
        self::$_reversi->main();

        $this->assertEquals(self::$_reversi->assign['isEnd'],                  $isEnd);
        $this->assertEquals(self::$_reversi->assign['move'],                   $move);
        $this->assertEquals(self::$_reversi->assign['myStoneColor'],           $myStoneColor);
        $this->assertEquals(self::$_reversi->assign['blackCount'],             $blackCount);
        $this->assertEquals(self::$_reversi->assign['whiteCount'],             $whiteCount);
        $this->assertEquals(self::$_reversi->assign['blackField'],             $blackField);
        $this->assertEquals(self::$_reversi->assign['whiteField'],             $whiteField);
        $this->assertEquals(self::$_reversi->assign['field'],                  $field);
        $this->assertEquals(self::$_reversi->assign['blackSetStonePositions'], $blackSetStonePositions);
        $this->assertEquals(self::$_reversi->assign['whiteSetStonePositions'], $whiteSetStonePositions);
    }

    /**
     * ゲーム進行中
     */
    public function test_mainInGame()
    {
        $isEnd        = false;
        $move         = 1;
        $myStoneColor = 1;
        $blackCount   = 4;
        $whiteCount   = 10;
        $blackField   = '3@5_5@4_1@5_2@5';
        $whiteField   = ['4@2_4@3_4@5_4@4_2@2_2@4_2@3_3@2_3@3_3@4','4@2_4@3_4@4_2@2_2@4_2@3_3@2_3@3_3@4_5@5'];
        $field        = [
            [
                0 => [0,0,0,0,0,0,0,0],
                1 => [0,0,0,0,0,1,0,0],
                2 => [0,0,2,2,2,1,0,0],
                3 => [0,0,2,2,2,1,0,0],
                4 => [0,0,2,2,2,2,0,0],
                5 => [0,0,0,0,1,0,0,0],
                6 => [0,0,0,0,0,0,0,0],
                7 => [0,0,0,0,0,0,0,0],
            ],
            [
                0 => [0,0,0,0,0,0,0,0],
                1 => [0,0,0,0,0,1,0,0],
                2 => [0,0,2,2,2,1,0,0],
                3 => [0,0,2,2,2,1,0,0],
                4 => [0,0,2,2,2,0,0,0],
                5 => [0,0,0,0,1,2,0,0],
                6 => [0,0,0,0,0,0,0,0],
                7 => [0,0,0,0,0,0,0,0],
            ],
        ];
        $blackSetStonePositions = [
            [
                1 => [3 => true,4 => true],
                2 => [1 => true],
                3 => [1 => true,6 => true],
                5 => [1 => true,2 => true,3 => true,5 => true],
            ],
            [
                1 => [3 => true,4 => true],
                2 => [1 => true],
                3 => [1 => true],
                5 => [1 => true,2 => true,3 => true,6 => true],
            ],
        ];
        $whiteSetStonePositions = [
            [
                0 => [5 => true,6 => true],
                1 => [6 => true],
                2 => [6 => true],
                3 => [6 => true],
                4 => [6 => true],
                6 => [3 => true,4 => true,5 => true],
            ],
            [
                0 => [6 => true],
                1 => [6 => true],
                2 => [6 => true],
                3 => [6 => true],
                4 => [6 => true],
                5 => [3 => true],
                6 => [4 => true,5 => true],
            ],
        ];

        // mainメソッド呼び出し
        self::$_reversi->main(1, 1, 5, 1, '3@5_5@4_4@4', '4@2_4@3_2@2_2@4_2@3_2@5_3@2_3@3_3@4');

        $this->assertEquals(self::$_reversi->assign['isEnd'],                    $isEnd);
        $this->assertEquals(self::$_reversi->assign['move'],                     $move);
        $this->assertEquals(self::$_reversi->assign['myStoneColor'],             $myStoneColor);
        $this->assertEquals(self::$_reversi->assign['blackCount'],               $blackCount);
        $this->assertEquals(self::$_reversi->assign['whiteCount'],               $whiteCount);
        $this->assertEquals(self::$_reversi->assign['blackField'],               $blackField);
        $this->assertContains(self::$_reversi->assign['whiteField'],             $whiteField);
        $this->assertContains(self::$_reversi->assign['field'],                  $field);
        $this->assertContains(self::$_reversi->assign['blackSetStonePositions'], $blackSetStonePositions);
        $this->assertContains(self::$_reversi->assign['whiteSetStonePositions'], $whiteSetStonePositions);
    }

    /**
     * ゲーム終了
     */
    public function test_mainEndGame()
    {
        $isEnd        = true;
        $move         = 1;
        $myStoneColor = 1;
        $blackCount   = 53;
        $whiteCount   = 11;
        $blackField   = '2@7_2@4_2@3_2@2_2@5_2@0_2@1_0@3_0@6_0@5_0@4_0@2_0@1_0@7_0@0_3@3_3@7_3@2_3@4_3@0_3@1_1@7_1@6_1@0_1@1_4@7_4@6_4@3_4@4_4@0_4@1_5@7_5@4_5@5_5@6_5@0_5@1_5@2_7@7_7@6_7@5_7@0_7@1_7@2_7@3_7@4_6@7_6@6_6@5_6@0_6@1_6@2_6@4';
        $whiteField   = '5@3_1@5_1@4_1@2_1@3_2@6_3@6_3@5_4@2_4@5_6@3';
        $field        = [
            0 => [1,1,1,1,1,1,1,1],
            1 => [1,1,2,2,2,2,1,1],
            2 => [1,1,1,1,1,1,2,1],
            3 => [1,1,1,1,1,2,2,1],
            4 => [1,1,2,1,1,2,1,1],
            5 => [1,1,1,2,1,1,1,1],
            6 => [1,1,1,2,1,1,1,1],
            7 => [1,1,1,1,1,1,1,1],
        ];
        $blackSetStonePositions = [];
        $whiteSetStonePositions = [];

        // mainメソッド呼び出し
        self::$_reversi->main(1, 7, 1, 1, '2@7_2@4_2@3_2@2_2@5_2@0_2@1_0@3_0@6_0@5_0@4_0@2_0@1_0@7_0@0_3@3_3@7_3@2_3@4_3@0_3@1_1@7_1@6_1@0_1@1_4@7_4@6_4@3_4@4_4@0_4@1_5@7_5@4_5@5_5@6_5@0_5@1_5@2_7@7_7@6_7@5_7@0_6@7_6@6_6@5_6@0_6@1_6@2_6@4', '5@3_1@5_1@4_1@2_1@3_2@6_3@6_3@5_4@2_4@5_7@2_7@4_7@3_6@3');

        $this->assertEquals(self::$_reversi->assign['isEnd'],                  $isEnd);
        $this->assertEquals(self::$_reversi->assign['move'],                   $move);
        $this->assertEquals(self::$_reversi->assign['myStoneColor'],           $myStoneColor);
        $this->assertEquals(self::$_reversi->assign['blackCount'],             $blackCount);
        $this->assertEquals(self::$_reversi->assign['whiteCount'],             $whiteCount);
        $this->assertEquals(self::$_reversi->assign['blackField'],             $blackField);
        $this->assertEquals(self::$_reversi->assign['whiteField'],             $whiteField);
        $this->assertEquals(self::$_reversi->assign['field'],                  $field);
        $this->assertEquals(self::$_reversi->assign['blackSetStonePositions'], $blackSetStonePositions);
        $this->assertEquals(self::$_reversi->assign['whiteSetStonePositions'], $whiteSetStonePositions);
    }
}
