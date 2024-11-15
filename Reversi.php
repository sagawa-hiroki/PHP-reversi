<?php

// HTTPリクエストを受ける
$myStoneColor = isset($_GET['myStoneColor']) ? $_GET['myStoneColor'] : Reversi::BLACK ;
$setY         = isset($_GET['setY'])         ? $_GET['setY']         : 0 ;
$setX         = isset($_GET['setX'])         ? $_GET['setX']         : 0 ;
$move         = isset($_GET['move'])         ? $_GET['move']         : Reversi::BLACK ;
$blackField   = isset($_GET['blackField'])   ? $_GET['blackField']   : '' ;
$whiteField   = isset($_GET['whiteField'])   ? $_GET['whiteField']   : '' ;

// インスタンス生成
$r = new Reversi();
// 処理を実行
$r->main($myStoneColor, $setY, $setX, $move, $blackField, $whiteField);

class Reversi
{
    const IS_END_IN_PLAY   = false; // ゲーム中
    const IS_END_GAME_OVER = true;  // ゲーム終了

    const NONE  = 0; // 何も置いていない
    const BLACK = 1; // 黒石
    const WHITE = 2; // 白石

    // フィールドの大きさ
    const MIN_HEIGHT = 0;
    const MIN_WIDTH  = 0;
    const MAX_HEIGHT = 8;
    const MAX_WIDTH  = 8;

    // 方向
    const UP_LEFT    = 1;
    const UP         = 2;
    const UP_RIGHT   = 3;
    const LEFT       = 4;
    const RIGHT      = 5;
    const DOWN_LEFT  = 6;
    const DOWN       = 7;
    const DOWN_RIGHT = 8;

    // comの難易度
    const COM_LEVEL_EASY = 1;
    const COM_LEVEL_HARD = 2;

    public  $assign                 = [];                   // アサイン
    private $blackField             = [];                   // 黒石が置いてある位置
    private $whiteField             = [];                   // 白石が置いてある位置
    private $field                  = [];                   // フィールド
    private $interleaveStone        = [];                   // 相手の石を挟む石
    private $interleaveComStone     = [];                   // 相手の石を挟む石
    private $isEnd                  = self::IS_END_IN_PLAY; // ゲーム終了フラグ
    private $isDefault              = true;                 // 石が初期状態ならばtrue
    private $myStoneColor           = self::BLACK;          // 自分の自石の色(初期は黒)
    private $enemyStoneColor        = self::WHITE;          // 相手の石の色
    private $blackSetStonePositions = [];                   // 黒石が置くとこの出来る場所
    private $whiteSetStonePositions = [];                   // 白石が置くことのできる場所
    private $move                   = self::BLACK;          // 手番の色

    /**
     * mainメソッド
     * - ゲームを実行する
     *
     * @param int    $myStoneColor      : 自分の自石の色(初期は黒)
     * @param int    $setY              : 石を置く位置(縦)
     * @param int    $setX              : 石を置く位置(横)
     * @param int    $move              : 手番の色
     * @param string $encodeBlackField  : 黒石が置かれている場所(エンコードしてある)
     * @param string $endcodeWhiteField : 白石が置かれている場所(エンコードしてある)
     */
    public function main($myStoneColor = self::BLACK, $setY = 0, $setX = 0, $move = self::BLACK, $encodeBlackField = '', $endcodeWhiteField = '')
    {
        $this->myStoneColor    = (int)$myStoneColor;
        $this->enemyStoneColor = $this->myStoneColor == self::BLACK ? self::WHITE : self::BLACK;
        $setY                  = (int)$setY;
        $setX                  = (int)$setX;
        $this->move            = (int)$move;

        // 初期画面ならばtrue
        $this->isDefault = $encodeBlackField == '' || $endcodeWhiteField == '';

        if($this->isDefault)
        {
            // 初期フィールド作成
            self::_setDefaultField();

            // comが黒番の場合先に com の石を置く
            if($this->myStoneColor == self::WHITE)
            {
                // comの石を置く
                self::_setComStone($this->enemyStoneColor);
            }
        }else
        {
            $this->blackField = self::_decodeStoneField($encodeBlackField);
            $this->whiteField = self::_decodeStoneField($endcodeWhiteField);

            // 自分が黒番
            if($this->myStoneColor == self::BLACK || $this->myStoneColor == self::WHITE)
            {
                // 自分の石を置く
                self::_setStone($this->myStoneColor, $setY, $setX);
                // comの石を置く
                self::_setComStone($this->enemyStoneColor);
            }
            // 対人
            else
            {
                // 石を置く
                self::_setStone($this->move, $setY, $setX);
            }
        }

        // 石の数を取得
        $blackCount = self::_getStoneCount($this->blackField);
        $whiteCount = self::_getStoneCount($this->whiteField);

        if($blackCount > 0)
        {
            $this->set('blackField', self::_encodeStoneField($this->blackField));
        }
        if($whiteCount > 0)
        {
            $this->set('whiteField', self::_encodeStoneField($this->whiteField));
        }

        $this->set('myStoneColor',           $this->myStoneColor);
        $this->set('field',                  $this->field);
        $this->set('blackCount',             $blackCount);
        $this->set('whiteCount',             $whiteCount);
        $this->set('blackSetStonePositions', $this->blackSetStonePositions);
        $this->set('whiteSetStonePositions', $this->whiteSetStonePositions);
        $this->set('move',                   $this->move);
        $this->set('isEnd',                  $this->isEnd);
    }

    /**
     * View部分にデータを渡す
     */
    private function set($key, $value)
    {
        $this->assign[$key] = $value;
    }

    /**
     * フィールド作成
     */
    private function _setField()
    {
        for($y = 0; $y < self::MAX_HEIGHT; $y++)
        {
            for($x = 0; $x < self::MAX_WIDTH; $x++)
            {
                if(isset($this->blackField[$y][$x]))
                {
                    $this->field[$y][$x] = self::BLACK;
                }
                elseif(isset($this->whiteField[$y][$x]))
                {
                    $this->field[$y][$x] = self::WHITE;
                }
                else
                {
                    $this->field[$y][$x] = self::NONE;
                }
            }
        }
    }

    /**
     * 初期フィールドを生成
     */
    private function _setDefaultField()
    {
        $this->blackField    = [
                        self::MAX_HEIGHT / 2 - 1 => [self::MAX_WIDTH / 2     => true],
                        self::MAX_HEIGHT / 2     => [self::MAX_WIDTH / 2 - 1 => true]
                    ];
        $this->whiteField    = [
                        self::MAX_HEIGHT / 2 - 1 => [self::MAX_WIDTH / 2 - 1 => true],
                        self::MAX_HEIGHT / 2     => [self::MAX_WIDTH / 2     => true]
                    ];

        self::_setField();

        $this->blackSetStonePositions = self::_getSetStonePositions($this->blackField, $this->whiteField);
        $this->whiteSetStonePositions = self::_getSetStonePositions($this->whiteField, $this->blackField);
    }

    /**
     * 石を置く
     * - 手番を変更する
     * - フィールドを新たに生成する
     *
     * @param int $myStoneColor : 自分の石の色
     * @param int $y            : 置く石の位置(縦)
     * @param int $x            : 置く石の位置(横)
     */
    private function _setStone($myStoneColor, $y, $x)
    {
        // フィールド作成(チェック用)
        self::_setField();

        // 相手の石の色
        $enemyStoneColor = $myStoneColor == self::BLACK ? self::WHITE : self::BLACK;

        // 自分と相手の石を動的に代入
        if($myStoneColor == self::BLACK)
        {
            $myField    = $this->blackField;
            $enemyField = $this->whiteField;
        }else{
            $myField    = $this->whiteField;
            $enemyField = $this->blackField;
        }

        // 石を置く(仮)
        $myField[$y][$x] = true;

        // 石を置く事ができるか
        $isSetStone = self::_checkSetStone($y, $x, $myField, $enemyField);
        if(!$isSetStone)
        {
            return;
        }
        // 挟む石が存在するのでその間をひっくり返す
        self::_getTuneStoneField($myStoneColor, $y, $x, $myField, $enemyField);

        // 石を置いた後の状態にするために石を配置する
        if($myStoneColor == self::BLACK)
        {
            $this->blackField = $myField;
            $this->whiteField = $enemyField;
        }else{
            $this->blackField = $enemyField;
            $this->whiteField = $myField;
        }

        // フィールド再作成
        self::_setField();

        // 石を置ける場所を取得
        $mySetStonePositions    = self::_getSetStonePositions($myField, $enemyField);
        $enemySetStonePositions = self::_getSetStonePositions($enemyField, $myField);

        if($myStoneColor == self::BLACK)
        {
            $this->blackSetStonePositions = $mySetStonePositions;
            $this->whiteSetStonePositions = $enemySetStonePositions;
        }else{
            $this->blackSetStonePositions = $enemySetStonePositions;
            $this->whiteSetStonePositions = $mySetStonePositions;
        }

        // 相手が次に置く事ができない場合、相手側のパス
        if(count($enemySetStonePositions) === 0)
        {
            // どちらも置く事ができない場合はゲーム終了
            if(count($mySetStonePositions) === 0)
            {
                $this->isEnd = self::IS_END_GAME_OVER;
            }else
            {
                // 相手がパス
                $this->move = $myStoneColor;
            }
        }else
        {
            // 手番を相手に渡す
            $this->move = $enemyStoneColor;
        }
    }

    /**
     * comが石を置く
     * - プレイヤーがパスの場合は再帰的にプレイヤーの手番になるまで石を置き続ける
     *
     * @param int $comStoneColor : comの石の色
     */
    private function _setComStone($comStoneColor)
    {
        // comがパス状態なので処理を終了
        if($comStoneColor != $this->move && $this->isDefault !== true)
        {
            return;
        }

        // 自分と相手の石を動的に代入
        if($comStoneColor == self::BLACK)
        {
            $myField    = $this->blackField;
            $enemyField = $this->whiteField;
        }else{
            $myField    = $this->whiteField;
            $enemyField = $this->blackField;
        }

        // comの石を置く
        $position = self::_getSetStonePosition($myField, $enemyField);
        if(count($position) !== 0)
        {
            self::_setStone($comStoneColor, $position['y'], $position['x']);
        }
        else
        {
            // 自分の石が置けるところを取得
            $position = self::_getSetStonePosition($enemyField, $myField);
            if(count($position) === 0)
            {
                // com 自分 どちらも置けない場合はゲーム終了
                $this->isEnd = self::IS_END_GAME_OVER;
            }
        }

        // 手番の変更が無いためもう一度 com の石を置く
        if($comStoneColor == $this->move && $this->isEnd == self::IS_END_IN_PLAY)
        {
            return self::_setComStone($comStoneColor);
        }
    }

    /**
     * 石をひっくり返す
     *
     * @param int   $myStoneColor : この色にする
     * @param int   $y            : ひっくり返す石の位置(縦)
     * @param int   $x            : ひっくり返す石の位置(横)
     * @param array &$myField     : 自分の持ち石
     * @param array &$enemyField  : 相手の持ち石
     */
    private function _setTuneStoneField($myStoneColor, $y, $x, array &$myField, array &$enemyField)
    {
        // 自分の石にする
        $myField[$y][$x] = true;
        // 相手の石を削除
        self::_unsetStone($y, $x, $enemyField);
        // フィールドの石の色を変える
        if($myStoneColor == self::BLACK)
        {
            $this->field[$y][$x] = self::BLACK;
        }else
        {
            $this->field[$y][$x] = self::WHITE;
        }
    }

    /**
     * 石を置く事ができるかチェック
     *
     * @param  int   $y          : ひっくり返す石の位置(縦)
     * @param  int   $x          : ひっくり返す石の位置(横)
     * @param  array $myField    : 自分の持ち石
     * @param  array $enemyField : 相手の持ち石
     * @return bool              : 置く事ができればtrue
     */
    private function _checkSetStone($y, $x, array $myField, array $enemyField)
    {
        // フィールドの範囲外
        if(!isset($this->field[$y][$x]))
        {
            return false;
        }
        // 既に石が置いてある
        if($this->field[$y][$x] != self::NONE)
        {
            return false;
        }
        // 隣に敵の石があるか
        $setSideEnemyStoneField = self::_getSetSideEnemyStoneField($y, $x, $enemyField);
        if(count($setSideEnemyStoneField) === 0)
        {
            return false;
        }
        // 挟む石があるか
        $this->interleaveStone = self::_getInterleaveStone($y, $x, $myField, $setSideEnemyStoneField);
        if(count($this->interleaveStone) === 0)
        {
            return false;
        }
        return true;
    }

    /**
     * 挟む定義に収まっているか
     *
     * @param  int  $checkY  : チェックする石の位置(縦)
     * @param  int  $checkX  : チェックする石の位置(横)
     * @param  bool $isCheck : チェックするか
     * @return bool          : 挟めていればtrue
     */
    private function _checkInterleave($checkY, $checkX, $isCheck)
    {
        // 既に挟む候補が見つかったため処理を終了
        if($isCheck === false)
        {
            return false;
        }
        // フィールドの範囲外
        if(!isset($this->field[$checkY][$checkX]))
        {
            return false;
        }
        // 敵の石との間に何も置いていない場所が存在する
        if($this->field[$checkY][$checkX] == self::NONE)
        {
            return false;
        }
        return true;
    }

    /**
     * 石を置く事のできる位置情報を一つ返す
     *
     * @param  array $myField    : 自分の石情報
     * @param  array $enemyField : 相手の石情報
     * @param  int   $level      : コンピュータの難易度レベル
     * @return array             : 石を置く事のできる位置情報
     */
    private function _getSetStonePosition(array $myField, array $enemyField, $level = self::COM_LEVEL_HARD)
    {
        $function = "_getSetStonePositionLevel{$level}";

        return self::$function($myField, $enemyField);
    }

    /**
     * com レベル 1
     * - 適当な場所に置くだけ
     */
    private function _getSetStonePositionLevel1(array $myField, array $enemyField)
    {
        foreach($this->field as $y => $v)
        {
            foreach($v as $x => $angle)
            {
                $isSetStone = self::_checkSetStone($y, $x, $myField, $enemyField);
                if($isSetStone)
                {
                    return [
                        'y' => $y,
                        'x' => $x
                    ];
                }
            }
        }
        return [];
    }

    /**
     * com レベル 2
     * - 最初は 4*4 のマスの中にしか置かない
     * - 4隅の不利になる場所には置かない
     * - 角を優先的に置くようにする
     */
    private function _getSetStonePositionLevel2(array $myField, array $enemyField)
    {
        // 優先的に置く場所
        $setPositions[0][0][0] = true;
        $setPositions[0][0][7] = true;
        $setPositions[0][7][0] = true;
        $setPositions[0][7][7] = true;
        $setPositions[1][2][2] = true;
        $setPositions[1][2][3] = true;
        $setPositions[1][2][4] = true;
        $setPositions[1][2][5] = true;
        $setPositions[1][3][2] = true;
        $setPositions[1][3][5] = true;
        $setPositions[1][4][2] = true;
        $setPositions[1][4][5] = true;
        $setPositions[1][5][2] = true;
        $setPositions[1][5][3] = true;
        $setPositions[1][5][4] = true;
        $setPositions[1][5][5] = true;

        // なるべく避けて置く場所
        $unsetPositions[1][1] = true;
        $unsetPositions[1][6] = true;
        $unsetPositions[6][6] = true;
        $unsetPositions[6][1] = true;
        $unsetPositions[1][0] = true;
        $unsetPositions[1][7] = true;
        $unsetPositions[6][0] = true;
        $unsetPositions[6][7] = true;
        $unsetPositions[0][1] = true;
        $unsetPositions[0][6] = true;
        $unsetPositions[7][1] = true;
        $unsetPositions[7][6] = true;

        $positions     = self::_getSetStonePositions($myField, $enemyField);
        $positionCount = self::_getStoneCount($positions);

        // 初期候補の時点で置く場所がない
        if($positionCount < 1)
        {
            return [];
        }
        //優先的に置く場所が候補の中にあればそちらを採用する
        foreach($setPositions as $v)
        {
            $hitSetPositions = self::_getSearchPositions($positions, $v);

            // 候補の中にある場合優先的に置く
            if(count($hitSetPositions) !== 0)
            {
                return self::_getLotPosition($hitSetPositions);
            }
        }

        // 候補をコピー
        $positionsBak = $positions;

        // 候補の中から不利になる手を取り除く
        foreach($positions as $y => $v)
        {
            foreach($v as $x => $vv)
            {
                if(isset($unsetPositions[$y][$x]))
                {
                    self::_unsetStone($y, $x, $positions);
                }
            }
        }
        // 候補の中にある場合
        if(count($positions) !== 0)
        {
            return self::_getLotPosition($positions);
        }
        // 取り除いたことで打つ場所がなくなってしまった場合は元の候補を返す
        return self::_getLotPosition($positionsBak);
    }

    /**
     * $basePositions の中に $searchPositions があれば全ての情報を返す
     *
     * @param  array $basePositions   : 検索対象の石情報
     * @param  array $searchPositions : 検索候補の石情報
     * @return array                  : 検索でヒットした石情報
     */
    private function _getSearchPositions(array $basePositions, array $searchPositions)
    {
        $hitPositions = [];
        foreach($basePositions as $y => $v)
        {
            foreach($v as $x => $vv)
            {
                if(isset($searchPositions[$y][$x]))
                {
                    $hitPositions[$y][$x] = true;
                }
            }
        }
        return $hitPositions;
    }

    /**
     * 与えられた候補の中から抽選して一つ場所を返す
     *
     * @param  array $positions : 抽選したい石情報
     * @return array            : 当選した石の位置
     */
    private function _getLotPosition(array $positions)
    {
        $positionCount = self::_getStoneCount($positions);
        $hitCount      = mt_rand(1, $positionCount);

        $position = [];
        $i        = 1;
        foreach($positions as $y => $v)
        {
            foreach($v as $x => $vv)
            {
                if($i == $hitCount)
                {
                    $position['y'] = $y;
                    $position['x'] = $x;

                    return $position;
                }
                $i++;
            }
        }
        return $position;
    }

    /**
     * 石を置く事のできる位置情報全てを返す
     *
     * @param  array $myField    : 自分の石情報
     * @param  array $enemyField : 相手の石情報
     * @return array             : 石を置く事のできる位置情報
     */
    private function _getSetStonePositions(array $myField, array $enemyField)
    {
        $positions = [];
        foreach($this->field as $y => $v)
        {
            foreach($v as $x => $angle)
            {
                $isSetStone = self::_checkSetStone($y, $x, $myField, $enemyField);
                if($isSetStone)
                {
                    $positions[$y][$x] = true;
                }
            }
        }
        return $positions;
    }

    /**
     * 挟む石が存在するのでその間をひっくり返す
     *
     * @param int   $myStoneColor : ひっくり返す石の色
     * @param int   $y            : ひっくり返す基準となる石の位置(縦)
     * @param int   $x            : ひっくり返す基準となる石の位置(横)
     * @param array &$myField     : ひっくり返す石情報
     * @param array &$enemyField  : ひっくり返される石情報
     */
    private function _getTuneStoneField($myStoneColor, $y, $x, array &$myField, array &$enemyField)
    {
        $up    = $y - 1;
        $down  = $y + 1;
        $left  = $x - 1;
        $right = $x + 1;

        foreach($this->interleaveStone as $y => $v)
        {
            foreach($v as $x => $angle)
            {
                // 左上
                if($angle == self::UP_LEFT)
                {
                    $checkY = $up;
                    $checkX = $left;
                    for($checkY = $checkY; $checkY > $y; $checkY--)
                    {
                        // 石をひっくり返す
                        self::_setTuneStoneField($myStoneColor, $checkY, $checkX, $myField, $enemyField);
                        $checkX--;
                    }
                }
                // 上
                elseif($angle == self::UP)
                {
                    $checkY = $up;
                    $checkX = $x;
                    for($checkY = $checkY; $checkY > $y; $checkY--)
                    {
                        // 石をひっくり返す
                        self::_setTuneStoneField($myStoneColor, $checkY, $checkX, $myField, $enemyField);
                    }
                }
                // 右上
                elseif($angle == self::UP_RIGHT)
                {
                    $checkY = $up;
                    $checkX = $right;
                    for($checkY = $checkY; $checkY > $y; $checkY--)
                    {
                        // 石をひっくり返す
                        self::_setTuneStoneField($myStoneColor, $checkY, $checkX, $myField, $enemyField);
                        $checkX++;
                    }
                }
                // 左
                elseif($angle == self::LEFT)
                {
                    $checkY = $y;
                    $checkX = $left;
                    for($checkX = $checkX; $checkX > $x; $checkX--)
                    {
                        // 石をひっくり返す
                        self::_setTuneStoneField($myStoneColor, $checkY, $checkX, $myField, $enemyField);
                    }
                }
                // 右
                elseif($angle == self::RIGHT)
                {
                    $checkY = $y;
                    $checkX = $right;
                    for($checkX = $checkX; $checkX < $x; $checkX++)
                    {
                        // 石をひっくり返す
                        self::_setTuneStoneField($myStoneColor, $checkY, $checkX, $myField, $enemyField);
                    }
                }
                // 左下
                elseif($angle == self::DOWN_LEFT)
                {
                    $checkY = $down;
                    $checkX = $left;
                    for($checkY = $checkY; $checkY < $y; $checkY++)
                    {
                        // 石をひっくり返す
                        self::_setTuneStoneField($myStoneColor, $checkY, $checkX, $myField, $enemyField);
                        $checkX--;
                    }
                }
                // 下
                elseif($angle == self::DOWN)
                {
                    $checkY = $down;
                    $checkX = $x;
                    for($checkY = $checkY; $checkY < $y; $checkY++)
                    {
                        // 石をひっくり返す
                        self::_setTuneStoneField($myStoneColor, $checkY, $checkX, $myField, $enemyField);
                    }
                }
                // 右下
                elseif($angle == self::DOWN_RIGHT)
                {
                    $checkY = $down;
                    $checkX = $right;
                    for($checkY = $checkY; $checkY < $y; $checkY++)
                    {
                        // 石をひっくり返す
                        self::_setTuneStoneField($myStoneColor, $checkY, $checkX, $myField, $enemyField);
                        $checkX++;
                    }
                }
            }
        }
    }

    /**
     * 相手の石を挟んでいる自分の石を取得
     *
     * @param  int   $y                      : 基準となる自分の石の位置(縦)
     * @param  int   $x                      : 基準となる自分の石の位置(横)
     * @param  array $myField                : 自分の石情報
     * @param  array $setSideEnemyStoneField : 隣接している相手の石情報
     * @return                               : 隣接している相手の石から最短にある自分の石の位置情報
     */
    private function _getInterleaveStone($y, $x, array $myField, array $setSideEnemyStoneField)
    {
        $interleaveStone = [];

        $up    = $y - 2;
        $down  = $y + 2;
        $left  = $x - 2;
        $right = $x + 2;

        foreach($setSideEnemyStoneField as $y => $v)
        {
            foreach($v as $x => $angle)
            {
                // 左上
                if($angle == self::UP_LEFT)
                {
                    $checkY = $up;
                    $checkX = $left;
                    $isInterleave = true;
                    for($checkY = $checkY; $checkY >= self::MIN_HEIGHT; $checkY--)
                    {
                        // 挟む定義に収まっているか
                        $isInterleave = self::_checkInterleave($checkY, $checkX, $isInterleave);
                        // 挟む石が存在する
                        if(isset($myField[$checkY][$checkX]) && $isInterleave === true)
                        {
                            $interleaveStone[$checkY][$checkX] = $angle;
                            $isInterleave                      = false; //チェック終了
                        }
                        $checkX--;
                    }
                }
                // 上
                elseif($angle == self::UP)
                {
                    $checkY = $up;
                    $checkX = $x;
                    $isInterleave = true;
                    for($checkY = $checkY; $checkY >= self::MIN_HEIGHT; $checkY--)
                    {
                        // 挟む定義に収まっているか
                        $isInterleave = self::_checkInterleave($checkY, $checkX, $isInterleave);
                        // 挟む石が存在する
                        if(isset($myField[$checkY][$checkX]) && $isInterleave === true)
                        {
                            $interleaveStone[$checkY][$checkX] = $angle;
                            $isInterleave                      = false; //チェック終了
                        }
                    }
                }
                // 右上
                elseif($angle == self::UP_RIGHT)
                {
                    $checkY = $up;
                    $checkX = $right;
                    $isInterleave = true;
                    for($checkY = $checkY; $checkY >= self::MIN_HEIGHT; $checkY--)
                    {
                        // 挟む定義に収まっているか
                        $isInterleave = self::_checkInterleave($checkY, $checkX, $isInterleave);
                        // 挟む石が存在する
                        if(isset($myField[$checkY][$checkX]) && $isInterleave === true)
                        {
                            $interleaveStone[$checkY][$checkX] = $angle;
                            $isInterleave                      = false; //チェック終了
                        }
                        $checkX++;
                    }
                }
                // 左
                elseif($angle == self::LEFT)
                {
                    $checkY = $y;
                    $checkX = $left;
                    $isInterleave = true;
                    for($checkX = $checkX; $checkX >= self::MIN_WIDTH; $checkX--)
                    {
                        // 挟む定義に収まっているか
                        $isInterleave = self::_checkInterleave($checkY, $checkX, $isInterleave);
                        // 挟む石が存在する
                        if(isset($myField[$checkY][$checkX]) && $isInterleave === true)
                        {
                            $interleaveStone[$checkY][$checkX] = $angle;
                            $isInterleave                      = false; //チェック終了
                        }
                    }
                }
                // 右
                elseif($angle == self::RIGHT)
                {
                    $checkY = $y;
                    $checkX = $right;
                    $isInterleave = true;
                    for($checkX = $checkX; $checkX <= self::MAX_WIDTH; $checkX++)
                    {
                        // 挟む定義に収まっているか
                        $isInterleave = self::_checkInterleave($checkY, $checkX, $isInterleave);
                        // 挟む石が存在する
                        if(isset($myField[$checkY][$checkX]) && $isInterleave === true)
                        {
                            $interleaveStone[$checkY][$checkX] = $angle;
                            $isInterleave                      = false; //チェック終了
                        }
                    }
                }
                // 左下
                elseif($angle == self::DOWN_LEFT)
                {
                    $checkY = $down;
                    $checkX = $left;
                    $isInterleave = true;
                    for($checkY = $checkY; $checkY <= self::MAX_HEIGHT; $checkY++)
                    {
                        // 挟む定義に収まっているか
                        $isInterleave = self::_checkInterleave($checkY, $checkX, $isInterleave);
                        // 挟む石が存在する
                        if(isset($myField[$checkY][$checkX]) && $isInterleave === true)
                        {
                            $interleaveStone[$checkY][$checkX] = $angle;
                            $isInterleave                      = false; //チェック終了
                        }
                        $checkX--;
                    }
                }
                // 下
                elseif($angle == self::DOWN)
                {
                    $checkY = $down;
                    $checkX = $x;
                    $isInterleave = true;
                    for($checkY = $checkY; $checkY <= self::MAX_HEIGHT; $checkY++)
                    {
                        // 挟む定義に収まっているか
                        $isInterleave = self::_checkInterleave($checkY, $checkX, $isInterleave);
                        // 挟む石が存在する
                        if(isset($myField[$checkY][$checkX]) && $isInterleave === true)
                        {
                            $interleaveStone[$checkY][$checkX] = $angle;
                            $isInterleave                      = false; //チェック終了
                        }
                    }
                }
                // 右下
                elseif($angle == self::DOWN_RIGHT)
                {
                    $checkY = $down;
                    $checkX = $right;
                    $isInterleave = true;
                    for($checkY = $checkY; $checkY <= self::MAX_HEIGHT; $checkY++)
                    {
                        // 挟む定義に収まっているか
                        $isInterleave = self::_checkInterleave($checkY, $checkX, $isInterleave);
                        // 挟む石が存在する
                        if(isset($myField[$checkY][$checkX]) && $isInterleave === true)
                        {
                            $interleaveStone[$checkY][$checkX] = $angle;
                            $isInterleave                      = false; //チェック終了
                        }
                        $checkX++;
                    }
                }
            }
        }
        return $interleaveStone;
    }

    /**
     * 隣に置いてある敵の石を返す
     *
     * @param  int   $y          : 自分が置こうとしている石の位置(縦)
     * @param  int   $x          : 自分が置こうとしている石の位置(横)
     * @param  array $enemyField : 相手の石情報
     * @return array             : 隣に置いてある敵の石
     */
    private function _getSetSideEnemyStoneField($y, $x, array $enemyField)
    {
        $setSideEnemyStoneField = [];

        $up    = $y - 1;
        $down  = $y + 1;
        $left  = $x - 1;
        $right = $x + 1;

        // 左上
        if(isset($enemyField[$up][$left]))
        {
            $setSideEnemyStoneField[$up][$left] = self::UP_LEFT;
        }
        // 上
        if(isset($enemyField[$up][$x]))
        {
            $setSideEnemyStoneField[$up][$x] = self::UP;
        }
        // 右上
        if(isset($enemyField[$up][$right]))
        {
            $setSideEnemyStoneField[$up][$right] = self::UP_RIGHT;
        }
        // 左
        if(isset($enemyField[$y][$left]))
        {
            $setSideEnemyStoneField[$y][$left] = self::LEFT;
        }
        // 右
        if(isset($enemyField[$y][$right]))
        {
            $setSideEnemyStoneField[$y][$right] = self::RIGHT;
        }
        // 左下
        if(isset($enemyField[$down][$left]))
        {
            $setSideEnemyStoneField[$down][$left] = self::DOWN_LEFT;
        }
        // 下
        if(isset($enemyField[$down][$x]))
        {
            $setSideEnemyStoneField[$down][$x] = self::DOWN;
        }
        // 右下
        if(isset($enemyField[$down][$right]))
        {
            $setSideEnemyStoneField[$down][$right] = self::DOWN_RIGHT;
        }

        return $setSideEnemyStoneField;
    }

    /**
     * 石の数を数える
     *
     * @param  array $colorStoneField : 石情報
     * @return int                    : 白または黒の石数
     */
    private function _getStoneCount(array $colorStoneField)
    {
        $stoneCount = 0;
        foreach($colorStoneField as $v)
        {
            $stoneCount += count($v);
        }
        return $stoneCount;
    }

    /**
     * 石を取り除く
     *
     * @param int   $unsetY     : 取り除きたい石の位置(縦)
     * @param int   $unsetX     : 取り除きたい石の位置(横)
     * @param array &$positions : 石情報
     */
    private function _unsetStone($unsetY, $unsetX, array &$positions)
    {
        foreach($positions as $y => $v)
        {
            foreach($v as $x => $vv)
            {
                if(isset($positions[$unsetY][$unsetX]))
                {
                    unset($positions[$unsetY][$unsetX]);

                    // $yの中に要素が無ければ $y を削除
                    if(count($positions[$unsetY]) === 0)
                    {
                        unset($positions[$unsetY]);
                    }
                }
            }
        }
    }

    /**
     * 石情報をエンコード
     *
     * @param  array $colorStoneField : 石情報
     * @return string                 : エンコードした石情報
     */
    private function _encodeStoneField(array $colorStoneField)
    {
        foreach($colorStoneField as $y => $v)
        {
            $y = (int)$y;
            foreach($v as $x => $vv)
            {
                $x = (int)$x;

                $str[] = "{$y}@{$x}";
            }
        }
        return implode('_' ,$str);
    }

    /**
     * 石情報をデコード
     *
     * @param  string $colorStoneField : 石情報
     * @return array|bool              : エンコード石情報を配列にして返す
     */
    private function _decodeStoneField($colorStoneField)
    {
        // 余計な文字列が入ってしまっている
        if(preg_match('/[\d@_]+/', $colorStoneField) !== 1){
            return false;
        }

        $tmp = explode('_', $colorStoneField);

        foreach($tmp as $v)
        {
            $tmp2 = explode('@', $v);

            $decodeStoneField[$tmp2[0]][$tmp2[1]] = true;
        }
        return $decodeStoneField;
    }
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=300" >
<title></title>
<style type="text/css">
body {
  margin: 0px;
  padding: 0px;
  text-align: center;
}
</style>
</head>
<body>

    <?php if(!$r->assign['isEnd']) : ?>
        <?php if($r->assign['move'] == Reversi::BLACK) : ?>
    <h1>黒番</h1>
        <?php else : ?>
    <h1>白番</h1>
        <?php endif; ?>

        <?php if($r->assign['myStoneColor'] == Reversi::BLACK) : ?>
    com 後手
    <a href="/Reversi.php?myStoneColor=<?php echo Reversi::WHITE ?>">com 先手</a>
    <a href="/Reversi.php?myStoneColor=0">対人</a>
        <?php elseif($r->assign['myStoneColor'] == Reversi::WHITE) : ?>
    <a href="/Reversi.php?myStoneColor=<?php echo Reversi::BLACK ?>">com 後手</a>
    com 先手
    <a href="/Reversi.php?myStoneColor=0">対人</a>
        <?php else : ?>
    <a href="/Reversi.php?myStoneColor=<?php echo Reversi::BLACK ?>">com 後手</a>
    <a href="/Reversi.php?myStoneColor=<?php echo Reversi::WHITE ?>">com 先手</a>
    対人
        <?php endif; ?>

        <br /><br />

    <table border="1" align="center">
        <tr>
            <td>黒 <?php echo $r->assign['blackCount'] ?></td>
            <td>白 <?php echo $r->assign['whiteCount'] ?></td>
        </tr>
    </table>

    <table border="1" align="center">
            <?php foreach ($r->assign['field'] as $y => $v) : ?>
        <tr height="20px" align="center" valign="middle">
                <?php foreach ($v as $x => $vv) : ?>
                    <?php if($vv == Reversi::BLACK) : ?>
            <td width="20px" align="center" valign="middle">●</td>
                    <?php elseif($vv == Reversi::WHITE) : ?>
            <td width="20px" align="center" valign="middle">○</td>
                    <?php else : ?>
            <td width="20px" align="center" valign="middle">
                        <?php if($r->assign['move'] == Reversi::BLACK) : ?>
                            <?php if(isset($r->assign['blackSetStonePositions'][$y][$x])) : ?>
                <a href="/Reversi.php?myStoneColor=<?php echo $r->assign['myStoneColor'] ?>&setY=<?php echo $y ?>&setX=<?php echo $x ?>&move=<?php echo $r->assign['move'] ?>&blackField=<?php echo $r->assign['blackField'] ?>&whiteField=<?php echo $r->assign['whiteField'] ?>">　</a>
                            <?php else : ?>

                            <?php endif; ?>
                        <?php else : ?>
                            <?php if(isset($r->assign['whiteSetStonePositions'][$y][$x])) : ?>
                <a href="/Reversi.php?myStoneColor=<?php echo $r->assign['myStoneColor'] ?>&setY=<?php echo $y ?>&setX=<?php echo $x ?>&move=<?php echo $r->assign['move'] ?>&blackField=<?php echo $r->assign['blackField'] ?>&whiteField=<?php echo $r->assign['whiteField'] ?>">　</a>
                            <?php else : ?>

                            <?php endif; ?>
                        <?php endif; ?>
            </td>
                    <?php endif; ?>
                <?php endforeach; ?>
        </tr>
            <?php endforeach; ?>
    </table>

    <?php else : ?>

    <h1>
    <?php if($r->assign['myStoneColor'] == Reversi::BLACK) : ?>
        <?php if($r->assign['blackCount'] > $r->assign['whiteCount']) : ?>
            プレイヤー勝利
        <?php elseif($r->assign['blackCount'] == $r->assign['whiteCount']) : ?>
            引き分け
        <?php else : ?>
            com勝利
        <?php endif; ?>
    <?php elseif($r->assign['myStoneColor'] == Reversi::WHITE) : ?>
        <?php if($r->assign['blackCount'] > $r->assign['whiteCount']) : ?>
            com勝利
        <?php elseif($r->assign['blackCount'] == $r->assign['whiteCount']) : ?>
            引き分け
        <?php else : ?>
            プレイヤー勝利
        <?php endif; ?>
    <?php else : ?>
        <?php if($r->assign['blackCount'] > $r->assign['whiteCount']) : ?>
            黒勝利
        <?php elseif($r->assign['blackCount'] == $r->assign['whiteCount']) : ?>
            引き分け
        <?php else : ?>
            白勝利
        <?php endif; ?>
    <?php endif; ?>
    </h1>

    <br />

    <a href="/Reversi.php?myStoneColor=<?php echo $r->assign['myStoneColor'] ?>">もう一度やる</a>

    <br /><br />

        <?php if($r->assign['myStoneColor'] == Reversi::BLACK) : ?>
    com 後手
    <a href="/Reversi.php?myStoneColor=<?php echo Reversi::WHITE ?>">com 先手</a>
    <a href="/Reversi.php?myStoneColor=0">対人</a>
        <?php elseif($r->assign['myStoneColor'] == Reversi::WHITE) : ?>
    <a href="/Reversi.php?myStoneColor=<?php echo Reversi::BLACK ?>">com 後手</a>
    com 先手
    <a href="/Reversi.php?myStoneColor=0">対人</a>
        <?php else : ?>
    <a href="/Reversi.php?myStoneColor=<?php echo Reversi::BLACK ?>">com 後手</a>
    <a href="/Reversi.php?myStoneColor=<?php echo Reversi::WHITE ?>">com 先手</a>
    対人
        <?php endif; ?>

    <br /><br />

    <table border="1" align="center">
        <tr>
            <td>黒 <?php echo $r->assign['blackCount'] ?></td>
            <td>白 <?php echo $r->assign['whiteCount'] ?></td>
        </tr>
    </table>

    <table border="1" align="center">
            <?php foreach ($r->assign['field'] as $y => $v) : ?>
        <tr height="20px" align="center" valign="middle">
                <?php foreach ($v as $x => $vv) : ?>

            <?php if($vv == Reversi::BLACK) : ?>
            <td width="20px" align="center" valign="middle">●</td>
            <?php elseif($vv == Reversi::WHITE) : ?>
            <td width="20px" align="center" valign="middle">○</td>
            <?php else : ?>
            <td width="20px" align="center" valign="middle">　</td>
            <?php endif; ?>

                <?php endforeach; ?>
        </tr>
            <?php endforeach; ?>
    </table>

    <?php endif; ?>

</body>
</html>
