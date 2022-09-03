<?php
require_once 'pbase.php';

/**
 * PF 機動戦士ガンダムユニコーン
 */
class Unicorn extends PachinkoBase implements IPachinko
{
    const ST_3R  = 0;
    const ST_10R = 1;

	/**
	 * @param int $atariCount		確率分母
	 * @param int $atariCount10R	RUSH中10R確率分母
	 * @param int $koatariCount		RUSH中転落小当たり確率分母
	 * @param int $rush				RUSH突入率
     * @param int $rush10R          10RRUSH突入率
	 * @param int $ball				初期持ち玉数
	 */
	function __construct($atariCount, $atariCount10R, $koatariCount, $rush, $rush10R, $ball)
	{
		$this->atariCount		= $atariCount;
		$this->atariCount10R	= $atariCount10R;
		$this->koatariCount = $koatariCount;
		$this->rush		    = $rush;
        $this->rush10R      = $rush10R;
        $this->totalRush    = $rush + $rush10R;
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
	private $koatariCount;
	private $rush;
    private $rush10R;
    private $totalRush;
	private $jitan = 10000;
	private $atariCount;
	private $returnBall = 3;
	private $normalBonusCount = 450;

    public function onInit()
	{
		$this->color("006900");
		echo '<div style="background-color:#ffffff; width: 1000px;"><canvas id="graph"></canvas></div>';
		$this->putGraph("unicorn");
		echo '<a id="data"></a>';
		$this->putData("unicorn");
		$this->atariCount = $this->atariCount;
		$this->initAtariArray($atariArray, $this->atariCount);
		$this->atariArray = $atariArray;

		$continueP = 0;
		$nextP = 1;
		$atariP = 1 / (self::ARRAY_SIZE / $this->atariCount10R);
		$koatariP = 1 / (self::ARRAY_SIZE / $this->koatariCount);
		for ($i = 0; $i < 500; $i++)
		{
			$continueP += $nextP * $atariP * (1 - $koatariP);
			$continueP += $nextP * $koatariP * (1 - pow((1 - $atariP), 5));
			$nextP *= (1 - $koatariP - $atariP * (1 - $koatariP));
		}
		
		o("\nDone.");
		o("\n======================================");
		o(" | PF 機動戦士ガンダムユニコーン");
		o(" | 大当たり確立: 1/" . round(self::ARRAY_SIZE / $this->atariCount, 2) . " -> 1/" . round(self::ARRAY_SIZE / $this->atariCount10R, 2));
		o(" | RUSH中小当たり確立: 1/" . round(self::ARRAY_SIZE / $this->koatariCount, 2));
		o(" | RUSH 突入率: " . $this->totalRush . "%");
		o(" | RUSH 継続率: " . $continueP * 100 . "%");
		o(" | ラウンド: 3R or 10R");
		o(" | 出玉: 450 or 1500 or 3000");
		o(" | 通常時 3R: 時短" . $this->jitan . "回: " . $this->rush . "%");
		o(" | 通常時 10R x 2: 時短" . $this->jitan . "回: " . $this->rush10R . "%");
        o(" | 通常時 3R: 時短0回: " . (100 - $this->totalRush) . "%");
		o(" | RUSH中 10R: 時短" . $this->jitan . "回: 100%");
		o("======================================");
		echo '<div id="text"></div>';
		echo '<img src="img/unicornimg.jpg"/><br/>';
		echo '<a href="https://github.com/FuckingCodeGenerator/P/blob/main/unicorn.php" target="_blank">
				<img src="../GithubLogo.png" alt="GitHubでソースコードを見る"/>
			</a>';
	}

    private function printGame($isAtari, $game, $ball, $usedBall, $isRush, $isRush10R, $zanho = false)
	{
		$skip = false;
		$reach = $isAtari ? true : $this->isReach();
		if ($zanho && !$isAtari)
			$reach = false;
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
                        if ($isRush10R || $zanho)
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
				if ($zanho)
					$str = "[残保留 残り" . (5 - $game) . "個] [" . $num1 . $num3 . $num2 . "]";
				else
					$str = "[" . $game . " GAME] 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | [" . $num1 . $num3 . $num2 . "]";
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
		if ($zanho)
			$str = "[残保留 残り" . (5 - $game) . "個] [" . $num1 . $num3 . $num2 . "]";
		else
			$str = "[" . $game . " GAME] 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | [" . $num1 . $num3 . $num2 . "]";
		$this->overridePrint($str);

		return [$num1, $num2, $num3, $rate];
	}

    /**
	 * ゲーム開始
	 *
	 * @return void
	 */
	public function start($gameId, $zanho = false)
	{
		set_time_limit(0);
		$game = 0;
		$usedBall = 0;
		$nums = [1, 3, 2, 0];
		$isAtari = false;
		$str = "[" . $game . " GAME] 持ち玉: " . $this->ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $this->ball * 4 . "円 | " . $nums[3] .  "回転/1k | [" . $nums[0] . $nums[2] . $nums[1] . "]";
		$this->overridePrint($str);

		if ($gameId != 0 && $game == 0 && $zanho)
		{
			$array10R = [];
			$this->initAtariArray($array10R, $this->atariCount10R);
			for ($i = 4; $i >= 0; $i--)
			{
				$game++;
				$isAtari = $this->isAtari($array10R, $this->genRand());
				$this->printGame($isAtari, $game, 0, 1, false, false, true);
				msleep(1000);
				if ($isAtari)
				{
					$this->enterRush(1500, $gameId, $game);
					return;
				}
			}
		}
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
				$this->overridePrint("大当");
				msleep(3000);
				if ($isRush10R)
				{
					$this->overridePrint("3000 FEVER", true);
					msleep(3000);
					$this->enterRush(3000, $gameId, $game);
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
					$this->bonus($this->normalBonusCount, false);
					msleep(1000);
					$this->overridePrint("BONUS 終了", true);
					msleep(1500);
					$this->overridePrint("獲得: " . $this->normalBonusCount . "pts", true);
					msleep(1000);
					$this->updateData("unicorn", $game, 1, $this->normalBonusCount, false);
					$this->start($gameId + 1);			
				}
			}
		}
	}

	private function bonus($count, $isHyper)
	{
		$counted = 0;
		while ($counted < $count)
		{
			$this->ball--;
			if ($this->isInRush())
			{
				$this->ball += 15;
				$counted += 15;
			}
			$round = floor(($counted - 15) / 150) + 1;
			if ($round > 10)
				$round -= 10;
			$str = "[ROUND " . $round . "] 持ち玉: " . $this->ball . "玉 | TOTAL " . $counted . "/" . $count;
			$this->overridePrint($str);
			msleep(100);
		}
		if ($isHyper)
			msleep(500);
		else
			msleep(2000);
	}

	/**
	 * RUSH 突入
	 *
	 * @param int $bonusCount	出玉
	 * @param int $gameId
	 * @param int $game
	 * @return void
	 */
	private function enterRush($bonusCount, $gameId, $game)
	{
		$this->bonus($bonusCount, false);
		msleep(1000);
		if ($bonusCount == 3000)
			$this->rush($bonusCount, $gameId, 3, $game);
		else
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
		$isHyper = $rushCount > 3;
		if ($isHyper)
			$rushName = "覚醒HYPER";
		else
			$rushName = "ユニコーン RUSH";
		if ($rushCount == 4)
		{
			msleep(1000);
			$this->overridePrint("3連続勝利達成", true);
			msleep(5000);
			$this->overridePrint("命を賭すことで　極限を超越した獣", true);
			msleep(4000);
			$this->overridePrint("ユニコーンガンダム　ついに―――", true);
			msleep(3500);
			$this->overridePrint("超　覚　醒", true);
			msleep(3000);
			$this->overridePrint("覚醒HYPER START", true);
			msleep(2000);
		}
		if ($rushCount == 1)
			$this->overridePrint("ユニコーンRUSH 突入", true);
		else if ($rushCount <= 3)
		{
			$this->overridePrint("超高速覚醒HYPERまであと" . (4 - $rushCount) . "回", true);
			msleep(2000);
			$this->overridePrint("ユニコーンRUSH 継続", true);
		}
		else if ($rushCount != 4)
			$this->overridePrint("UNICORN SPECIAL FEVER " . ($rushCount - 1) . "回目", true);
		msleep(2000);
		$array10R = [];
		$arrayKoatari = [];
		$this->initAtariArray($array10R, $this->atariCount10R);
		$this->initAtariArray($arrayKoatari, $this->koatariCount);
		for ($i = $this->jitan; $i >= 0; $i--)
		{
			if (!$this->isInRush())
			{
				$this->ball--;
				$i++;
				continue;
			}
			do
			{
				$rand = $this->genRand();
				$at10R = $this->isAtari($array10R, $rand);
				$koatari = $this->isAtari($arrayKoatari, $rand);
			} while ($at10R && $koatari);

			if ($at10R)
			{
                $num1 = 7;
                $num2 = 7;
                $num3 = 7;
			}
			else if ($koatari)
			{
				$num1 = "E";
				$num2 = "N";
				$num3 = "D";
			}
			else
			{
				$num1 = $this->getNumber();
				$num2 = $this->getNumber();
				do
				{
					$num3 = $this->getNumber();
				} while ($num3 == $num2);
				if ($this->tryRand(13))
					$num1 = "E";
				else if ($this->tryRand(15))
				{
					$num1 = "E";
					$num3 = "D";
				}
				else if ($this->tryRand(15))
				{
					$num2 = "N";
					$num3 = "D";
				}
			}
            $str = "[" . $rushName . "] [" . $num1 . $num2 . $num3 . "] | TOTAL " . $counted . " pts";
            $this->overridePrint($str);
            if ($at10R)
            {
				if ($isHyper)
                	msleep(500);
				else
					msleep(2500);
                $this->bonus(1500, $isHyper);
                $counted += 1500;
                $this->rush($counted, $gameId + 1, $rushCount + 1, $game);
                return;
            }
			else if ($koatari)
			{
				msleep(3000);
				$this->overridePrint($rushName . " 終了");
				msleep(1500);
				$this->overridePrint("HYPER x " . $rushCount . " | " . sprintf("%05d", $counted) . " pts");
				msleep(3000);
				$this->updateData("unicorn", $game, $rushCount, $counted, true);
				$this->start($gameId + 1, true);
			}
			if ($isHyper)
            	msleep(300);
			else
				msleep(2000);
		}
	}
}

o("Initializing. Please wait...");

$unicorn = new Unicorn(205, 1594, 426, 40, 20, 0);
$unicorn->onInit();
$unicorn->start(0);