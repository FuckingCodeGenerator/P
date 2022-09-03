<?php
require_once 'pbase.php';

/**
 * P Re: ゼロから始める異世界生活　鬼がかりver.
 */
class ReZero extends PachinkoBase implements IPachinko
{
	const AT_TYPE_NONE = 0;
	const AT_TYPE_20R = 1;
	const AT_TYPE_2R = 2;
	const AT_TYPE_10R = 3;
	const AT_TYPE_RESTART = 4;

	/**
	 * @param int $atariCount		確率分母
	 * @param int $atariCount10R	RUSH中10R確率分母
	 * @param int $atariCount0R		RUSH中突然確変確率分母
	 * @param int $rush				RUSH突入率
	 * @param int $st				ST回転数
	 * @param float $p20R			RUSH中20R割合
	 * @param int $ball				初期持ち玉数
	 */
	function __construct($atariCount, $atariCount10R, $atariCount0R, $rush, $st, $p20R, $ball)
	{
		$this->atariCount		= $atariCount;
		$this->atariCount10R	= $atariCount10R;
		$this->atariCount0R		= $atariCount0R;
		$this->totalAC			= $atariCount + $atariCount10R + $atariCount0R;
		$this->rush		= $rush;
		$this->p20R		= $p20R;
		$this->st		= $st;
		$this->ball		= $ball;
	}

	/**
	 * 当たり数字一覧
	 *
	 * @var array
	 */
	private $atariArray = [];

	private $ball;

	private $atariCount10R;
	private $atariCount0R;
	private $totalAC;
	private $rush;
	private $st;
	private $p20R;
	private $atariCount;
	private $returnBall = 1;
	private $normalBonusCount = 1500;
	private $rushBonusCount = 3000;

	public function onInit()
	{
		$this->color("786CAE");
		echo '<div style="background-color:#ffffff; width: 1000px;"><canvas id="graph"></canvas></div>';
		$this->putGraph("rezero");
		echo '<a id="data"></a>';
		$this->putData("rezero");

		$this->atariCount = $this->atariCount;
		$this->initAtariArray($atariArray, $this->atariCount);
		$this->atariArray = $atariArray;
		
		o("\nDone.");
		o("\n======================================");
		o(" | P Re:ゼロから始める異世界生活 鬼がかりver.");
		o(" | 大当たり確立: 1/" . round(self::ARRAY_SIZE / $this->atariCount, 2) . " -> 1/" . round(self::ARRAY_SIZE / $this->totalAC, 2));
		o(" | RUSH 突入率: " . $this->rush . "%");
		o(" | RUSH 継続率: " . (1 - (pow((1 - 1 / (self::ARRAY_SIZE / $this->totalAC)), $this->st))) * 100 . "%");
		o(" | ラウンド: 2R or 10R");
		o(" | 出玉: 0 or 300 or 1500 or 3000");
		o(" | 通常時 10R x 2: ST144: " . $this->rush . "%");
		o(" | 通常時 10R: ST0: " . (100 - $this->rush) . "%");
		o(" | RUSH中 10R x 2: ST144: " . round($this->atariCount / $this->totalAC * 100 * $this->p20R, 2) . "%");
		o(" | RUSH中 10R: ST144: " . round($this->atariCount10R / $this->totalAC * 100, 2) . "%");
		o(" | RUSH中 2R: ST144: " . round($this->atariCount / $this->totalAC * 100 * (1 - $this->p20R), 2) . "%");
		o(" | RUSH中 突然確変: ST144: " . round($this->atariCount0R / $this->totalAC * 100, 2) . "%");
		o("======================================");
		echo '<div id="text"></div>';
		echo '<img src="img/rezeroimg.jpg"/><br/>';
		echo '<a href="https://github.com/FuckingCodeGenerator/P/blob/main/rezero.php" target="_blank">
				<img src="../GithubLogo.png" alt="GitHubでソースコードを見る"/>
			</a>';
	}

	private function printGame($isAtari, $game, $ball, $usedBall, $isRush)
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
							$nums = [1, 3, 5, 7];
						else
							$nums = [2, 4, 6, 8];
						$num1 = $nums[mt_rand(0, 3)];
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
				$str = "[" . $game . "G] 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | [" . $num1 . $num3 . $num2 . "]";
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
		$str = "[" . $game . "G] 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | [" . $num1 . $num3 . $num2 . "]";
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
		$str = "[" . $game . "G] 持ち玉: " . $this->ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $this->ball * 4 . "円 | " . $nums[3] .  "回転/1k | [" . $nums[0] . $nums[2] . $nums[1] . "]";
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
				$nums = $this->printGame($isAtari, $game, $this->ball, $usedBall, $isRush);
				msleep(500);
			}

			if ($isAtari)
			{
				msleep(1000);
				$this->overridePrint("大当");
				msleep(3000);
				if ($isRush)
				{
					$this->overridePrint("RUSH 確定");
					msleep(3000);
					$this->enterRush($gameId, $game);
				}
				else
				{
					$this->overridePrint("通常確定");
					msleep(3000);
					$this->bonus($this->normalBonusCount);
					msleep(1000);
					$this->overridePrint("BONUS 終了");
					$this->overridePrint("獲得: " . $this->normalBonusCount . "pt");
					msleep(2000);
					$this->updateData("rezero", $game, 1, $this->normalBonusCount, false);
					$this->start($gameId + 1);			
				}
			}
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
			$str = "[" . $count . "BONUS] 持ち玉: " . $this->ball . "玉 | " . $counted . "/" . $count . "pt";
			$this->overridePrint($str);
			msleep(100);
		}
		msleep(2000);
	}

	/**
	 * RUSH 突入
	 *
	 * @param int $gameId
	 * @param int $game		ゲーム数
	 * @return void
	 */
	private function enterRush($gameId, $game)
	{
		$this->bonus($this->rushBonusCount);
		msleep(500);
		$this->rush($this->rushBonusCount, $gameId, $game, 1);
	}

	/**
	 * RUSH
	 *
	 * @param int $counted	獲得済み玉数
	 * @param int $gameId
	 * @param int $game		ゲーム数
	 * @param int $rushCount
	 * @return void
	 */
	private function rush($counted, $gameId, $game, $rushCount)
	{
		if ($counted == $this->rushBonusCount)
			$this->overridePrint("鬼がかりRUSH 突入", true);
		else
			$this->overridePrint("鬼がかりRUSH 継続", true);
		msleep(2000);
		$array10R = [];
		$array0R = [];
		$atariType = self::AT_TYPE_NONE;
		$this->initAtariArray($array10R, $this->atariCount10R);
		$this->initAtariArray($array0R, $this->atariCount0R);
		for ($i = $this->st; $i >= 0; $i--)
		{
			$rand = $this->genRand();
			if ($this->isAtari($this->atariArray, $rand))
			{
				if ($this->is20R($this->p20R * 100))
				{
					$atariType = self::AT_TYPE_20R;
					if ($this->tryRand(2))
						$num1 = 3;
					else
						$num1 = 7;
				}
				else
				{
					$atariType = self::AT_TYPE_2R;
					if ($this->tryRand(2))
						$num1 = 2;
					else
						$num1 = 6;
				}
				$num2 = $num1;
				$num3 = $num1;
			}
			else if ($this->isAtari($array10R, $rand))
			{
				$atariType = self::AT_TYPE_10R;
				if ($this->tryRand(2))
					$num1 = 1;
				else
					$num1 = 5;
				$num2 = $num1;
				$num3 = $num1;
			}
			else if ($this->isAtari($array0R, $rand))
			{
				$atariType = self::AT_TYPE_RESTART;
				$num1 = "Re:";
				$num2 = $num1;
				$num3 = $num1;
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
			$str = "[鬼がかりRUSH] 残り" . $i . "回 | " . $counted . "pt | [" . $num1 . $num2 . $num3 . "]";
			$this->overridePrint($str);

			if ($atariType != self::AT_TYPE_NONE)
			{
				msleep(1000);
				$bonusCount = 0;
				switch ($atariType)
				{
					case self::AT_TYPE_20R: $bonusCount = 3000; break;
					case self::AT_TYPE_10R: $bonusCount = 1500; break;
					case self::AT_TYPE_2R:	$bonusCount = 300;	break;
				}
				if ($bonusCount == 0)
				{
					$this->overridePrint("Re: START");
					msleep(1000);
					$this->rush($counted, $gameId + 1, $game, $rushCount + 1);
					return;
				}
				$this->bonus($bonusCount);
				$counted += $bonusCount;
				$this->rush($counted, $gameId + 1, $game, $rushCount + 1);
				return;
			}
			msleep(650);
		}
		$this->overridePrint("RUSH 終了", true);
		msleep(1000);
		$this->overridePrint("獲得: " . $counted . "pt");
		msleep(2000);
		$this->updateData("rezero", $game, $rushCount, $counted, true);
		$this->start($gameId + 1);
	}
}

o("Initializing. Please wait...");

$reZero = new ReZero(205, 360, 92, 55, 144, 0.8, 0);
$reZero->onInit();
$reZero->start(0);