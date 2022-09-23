<?php
require_once 'pbase.php';

/**
 * 新世紀エヴァンゲリオン〜未来への咆哮〜
 */
class Evangelion extends PachinkoBase implements IPachinko
{
    const ST_3R  = 0;
    const ST_10R = 1;

	/**
	 * @param int $atariCount		確率分母
	 * @param int $atariCount10R	RUSH中10R確率分母
	 * @param int $rush				RUSH突入率
     * @param int $rush10R          10RRUSH突入率
	 * @param int $st				ST回転数
	 * @param int $ball				初期持ち玉数
	 */
	function __construct($atariCount, $atariCount10R, $rush, $rush10R, $st, $ball)
	{
		$this->atariCount		= $atariCount;
		$this->atariCount10R	= $atariCount10R;
		$this->rush		    = $rush;
        $this->rush10R      = $rush10R;
        $this->totalRush    = $rush + $rush10R;
		$this->st		    = $st;
		$this->ball		    = $ball;
	}

	/**
	 * 当たり数字一覧
	 *
	 * @var array
	 */
	private $atariArray = [];

	private $ball;

	private $atariCount10R;
	private $rush;
    private $rush10R;
    private $totalRush;
	private $st;
	private $atariCount;
	private $returnBall = 1;
	private $normalBonusCount = 450;
    private $jitan = 100;

    public function onInit()
	{
		$this->color("5b496c");
		echo '<div id="startup"></div>';
		echo '<div style="background-color:#ffffff; width: 1000px;"><canvas id="graph"></canvas></div>';
		$this->putGraph("eva");
		echo '<a id="data"></a>';
		$this->putData("eva");

		$this->atariCount = $this->atariCount;
		$this->initAtariArray($atariArray, $this->atariCount);
		$this->atariArray = $atariArray;
		
		o("\nDone.");
		o("\n======================================");
		o(" | 新世紀エヴァンゲリオン〜未来への咆哮〜");
		o(" | 大当たり確立: 1/" . round(self::ARRAY_SIZE / $this->atariCount, 2) . " -> 1/" . round(self::ARRAY_SIZE / $this->atariCount10R, 2));
		o(" | RUSH 突入率: " . $this->totalRush . "%");
		o(" | RUSH 継続率: " . (1 - (pow((1 - 1 / (self::ARRAY_SIZE / $this->atariCount10R)), $this->st))) * 100 . "%");
		o(" | ラウンド: 3R or 10R");
		o(" | 出玉: 450 or 1500");
		o(" | 通常時 3R: ST" . $this->st . ": " . $this->rush . "%");
		o(" | 通常時 10R: ST" . $this->st . ": " . $this->rush10R . "%");
        o(" | 通常時 3R: 時短" . $this->jitan . ": " . (100 - $this->totalRush) . "%");
		o(" | RUSH中 10R: ST" . $this->st . ": 100%");
		o("======================================");
		echo '<div id="text"></div>';
		echo '<img src="img/evaimg.jpg"/><br/>';
		echo '<a href="https://github.com/FuckingCodeGenerator/P/blob/main/eva.php" target="_blank">
				<img src="../GithubLogo.png" alt="GitHubでソースコードを見る"/>
			</a>';
	}

    private function printGame($isAtari, $game, $ball, $usedBall, $isRush, $isRush10R)
	{
		$skip = false;
		$reach = $isAtari ? true : $this->isReach();
		$rate = round(($game / ($usedBall * 4)) * 1000, 2);
		for ($l = 0; $l < 3; $l++)
		{
			for ($i = 0; $i < 10; $i++)
			{
				if ($l == 0)
				{
					$num1 = $this->getNumber();
					if ($i == 9 && $isAtari)
					{
						if ($isRush)
                        {
							$nums = [1, 3, 5];
                            $num1 = $nums[mt_rand(0, 2)];
                        }
						else
                        {
							$nums = [2, 4, 6, 8];
                            $num1 = $nums[mt_rand(0, 3)];
                        }
                        if ($isRush10R)
                            $num1 = 7;
					}
				}
				if ($l <= 1)
					$num2 = $this->getNumber();
				else
				{
					if ($reach)
						$num2 = $num1;
					if (!$reach && $num2 == $num1)
						$num2 = $this->getNumber($num2);
				}
				$num3 = $this->getNumber();
				$str = "[回転数 " . $game . "] 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | [" . $num1 . $num3 . $num2 . "]";
				if ($reach && $l == 2 && $i == 9)
				{
					if (!$skip)
					{
						$i = 0;
						$skip = true;
					}
				}
				if ($reach && $l == 2)
					$str .= " [リーチ]";
				$this->overridePrint($str);
				msleep(10);
			}
		}

		if ($isAtari)
			$num3 = $num1;
		else
		{
			do
			{
				$num3 = $this->getNumber();
			} while ($num3 == $num2);
		}
		$str = "[回転数 " . $game . "] 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | [" . $num1 . $num3 . $num2 . "]";
		$this->overridePrint($str);

		return [$num1, $num2, $num3, $rate];
	}

    /**
	 * ゲーム開始
	 *
	 * @return void
	 */
	public function start($gameId)
	{
		set_time_limit(0);
		$game = 0;
		$usedBall = 0;
		$nums = [1, 3, 2, 0];
		$isAtari = false;
		$str = "[回転数 " . $game . "] 持ち玉: " . $this->ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $this->ball * 4 . "円 | " . $nums[3] .  "回転/1k | [" . $nums[0] . $nums[2] . $nums[1] . "]";
		$this->overridePrint($str);
		while (1)
		{
			$this->ball--;
			$usedBall++;
			
			if ($this->isIn())
			{
				$game++;
				$this->ball += $this->returnBall;
				$isAtari = $this->isAtari($this->atariArray, $this->genRand());
				$isRush = $this->isRush($this->rush);
                $isRush10R = $this->isRush($this->rush10R);
				$nums = $this->printGame($isAtari, $game, $this->ball, $usedBall, $isRush, $isRush10R);
				msleep(500);
			}

			if ($isAtari)
			{
				msleep(1000);
				$this->overridePrint("大当", true);
				msleep(3000);
				if ($isRush10R)
				{
					$this->overridePrint("全回転", true);
					msleep(3000);
					$this->enterRush(1500, $gameId, $game);
				}
				else if ($isRush)
                {
					$this->overridePrint("RUSH 確定");
					msleep(3000);
					$this->enterRush($this->normalBonusCount, $gameId, $game);
                }
                else
				{
					$this->overridePrint("通常確定");
					msleep(3000);
					$this->bonus($this->normalBonusCount);
					msleep(1000);
					$this->overridePrint("BONUS 終了");
					msleep(1000);
					$this->overridePrint("獲得: " . $this->normalBonusCount . "pt");
					msleep(1000);
                    $this->chanceTime($gameId, $game);
					$this->updateData("eva", $game, 1, $this->normalBonusCount, false);
					$this->start($gameId + 1);			
				}
			}
		}
	}

    private function chanceTime($gameId, $game)
    {
        $this->overridePrint("チャンスタイム " . $this->jitan . "回", true);
        msleep(2000);
		for ($i = $this->jitan; $i >= 0; $i--)
		{
			$rand = $this->genRand();
			if ($this->isAtari($this->atariArray, $rand))
			{
                $num1 = 7;
                $num2 = 7;
                $num3 = 7;
			}
			else
			{
				$num1 = $this->getNumber();
				$num2 = $this->getNumber();
				do
				{
					$num3 = $this->getNumber();
				} while ($num3 == $num2);

            }
            $str = "[CHANCE] 残り" . $i . "回 | [" . $num1 . $num2 . $num3 . "]";
            $this->overridePrint($str);
            if ($num3 == $num2)
            {
                msleep(2000);
                $this->bonus(1500);
                $this->rush(1950, $gameId, 1, $game);
                return;
            }
            msleep(650);
        }  
    }

	private function bonus($count)
	{
		$counted = 0;
		$id = mt_rand(9999, 99999999);
		while ($counted < $count)
		{
			$this->ball--;
			if ($this->isInRush())
			{
				$this->ball += 15;
				$counted += 15;
			}
			$str = "[完全決着] 持ち玉: " . $this->ball . "玉 | 現在値 " . $counted . "/" . $count . "pt";
			$this->overridePrint($str);
			msleep(100);
		}
		msleep(2000);
	}

	/**
	 * RUSH 突入
	 *
	 * @param int $bonusCount
	 * @param int $gameId
	 * @param int $game
	 * @return void
	 */
	private function enterRush($bonusCount, $gameId, $game)
	{
		$this->bonus($bonusCount);
		msleep(500);
		$this->rush($bonusCount, $gameId, 1, $game);
	}

	/**
	 * RUSH
	 *
	 * @param int $counted	獲得済み玉数
	 * @param int $gameId
     * @param int $rushCount
	 * @param int $game
	 * @return void
	 */
	private function rush($counted, $gameId, $rushCount, $game)
	{
		if ($rushCount == 1)
			$this->overridePrint("IMPACT MODE 突入", true);
		else
			$this->overridePrint("IMPACT MODE 継続", true);
		msleep(2000);
		$array10R = [];
		$this->initAtariArray($array10R, $this->atariCount10R);
		for ($i = $this->st; $i >= 0; $i--)
		{
			$rand = $this->genRand();
			if ($this->isAtari($array10R, $rand))
			{
                $num1 = 7;
                $num2 = 7;
                $num3 = 7;
			}
			else
			{
				$num1 = $this->getNumber();
				$num2 = $this->getNumber();
				do
				{
					$num3 = $this->getNumber();
				} while ($num3 == $num2);
			}
            $str = "[IMPACT MODE] 残り" . $i . "回 | TOTAL " . $counted . " GET | [" . $num1 . $num2 . $num3 . "]";
            $this->overridePrint($str);
            if ($num3 == $num2)
            {
                msleep(2000);
                $this->overridePrint("開放", true);
                msleep(1500);
                $this->overridePrint("完全決着", true);
                msleep(2000);
                $this->overridePrint($rushCount + 1 . "回目", true);
                msleep(2500);
                $this->bonus(1500);
                $counted += 1500;
                $this->rush($counted, $gameId + 1, $rushCount + 1, $game);
                return;
            }
            msleep(650);
		}
		if ($counted != $this->normalBonusCount)
			$rushCount--;
		$this->overridePrint("IMPACT MODE 終了", true);
        msleep(1500);
		$this->overridePrint("BONUS x " . $rushCount . " | " . sprintf("%05d", $counted) . "pt", true);
		msleep(3000);
		$this->updateData("eva", $game, $rushCount, $counted, true);
		$this->start($gameId + 1);
	}
}

o("Initializing. Please wait...");

$eva = new Evangelion(205, 656, 56, 3, 163, 0);
$eva->onInit();
$eva->start(0);