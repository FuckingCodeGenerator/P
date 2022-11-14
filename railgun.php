<?php
require_once 'pbase.php';

/**
 * Pとある科学の超電磁砲
 */
class Railgun extends PachinkoBase implements IPachinko
{
	const AT_TYPE_NONE = 0;
	const AT_TYPE_4R = 1;
	const AT_TYPE_10R = 2;

	function __construct($atariCount, $acJitan, $at4R, $at10R, $denchuLongP, $rush, $jitan, $ball)
	{
		$this->atariCount	= $atariCount;
		$this->acJitan		= $acJitan;
		$this->at4R	    	= $at4R;
		$this->at10R		= $at10R;
		$this->denchuLongP 	= $denchuLongP;
		$this->rush			= $rush;
		$this->jitan		= $jitan;
		$this->ball			= $ball;
	}

	/**
	 * 当たり数字一覧
	 *
	 * @var array
	 */
	private $atariArray = [];

	private $ball;

	private $at4R;
    private $at10R;
	private $rush;
	private $jitan;
	private $extraJitan = 3000;
	private $denchuLongP;
	private $p20R;
	private $atariCount;
	private $acJitan;
	private $returnBall = 3;
	private $shokyu4R = 5;
	private $shokyu10R = 15;
	private $normalBonusCount = 400;

	public function onInit()
	{
		$this->color("ED7632", "000000");
		echo '<div style="background-color:#ffffff; width: 1000px;"><canvas id="graph"></canvas></div>';
		$this->putGraph("railgun");
		echo '<a id="data"></a>';
		$this->putData("railgun");

		$this->atariCount = $this->atariCount;
		$this->initAtariArray($atariArray, $this->atariCount);
		$this->atariArray = $atariArray;
		
		$huzuP = round(self::ARRAY_SIZE / $this->acJitan, 2);
		$jEnterP = (1 - (pow((1 - 1 / ($huzuP / ($this->denchuLongP / 100))), $this->jitan))) * 100;
		$nInRush = 100 - $this->rush;
		$nInRushJ = 100 - $jEnterP;
		$realNInP = $nInRush * $nInRushJ / 100;
		$totalInP = 100 - $realNInP;

		$kzokuP = (1 - (pow((1 - 1 / $huzuP), $this->jitan))) * 100;
		$nKzoku = 100 - $kzokuP;
		$nExtra = 100 - ($this->at10R / 2);
		$realNK = $nKzoku * $nExtra / 100;
		$totalKzokuP = 100 - $realNK;

		$at4R = $this->at4R / 100;
		$at4RP = pow($at4R, 3) * 100;
		
		o("\nDone.");
		o("\n======================================");
		o(" | Pとある科学の超電磁砲");
		o(" | 大当たり確立: 1/" . round(self::ARRAY_SIZE / $this->atariCount, 2) . " -> 1/" . $huzuP);
		o(" | RUSH 突入率: " . $this->rush . "% (時短引き戻し含確率: " . $totalInP . "%)");
		o(" | RUSH 継続率: " . round($kzokuP, 5) . "% (時短3000回込み継続率: " . round($totalKzokuP, 5) . "%)");
		o(" | 賞球数: 3 and 1 and 5 and 15");
		o(" | ラウンド: 4R or 8R or 10R (4R or 8R は賞球数5, 10R は賞球数15)");
		o(" | 表記出玉: 410 or 650 or 2000 or 3350 or 4700");
		o(" | 正出玉: 400 or 600 or 1900 or 3200 or 4500");
		o(" | >> (表記差玉分は賞球数1のフロック入賞時獲得水増出玉)");
		o(" | 通常時 8R: 時短 " . $this->jitan . "回: " . $this->rush . "%");
		o(" | 通常時 8R: 時短 " . $this->jitan . "回 (電チューショート開放振り分け有): " . (100 - $this->rush) . "%");
		o(" | 時短中(非RUSH) 電チュー開放小当たり確率: 1/" . $huzuP);
		o(" | 時短中(非RUSH) 電チューショート開放確率: " . (100 - $this->denchuLongP) . "%");
		o(" | 時短中(非RUSH) 電チューロング開放確率: " . $this->denchuLongP . "%");
		o(" | 時短中(非RUSH) 電チューロング開放合算確率: 1/" . round($huzuP / ($this->denchuLongP / 100), 2));
		o(" | RUSH中 電チューロング開放確率: 100%");
		o(" | RUSH中 10R (大当たりストック1,2個目): 時短 " . $this->jitan . "回: " . $this->at10R . "%");
		o(" | RUSH中 10R (大当たりストック3個目): 時短 " . $this->jitan . "回: " . ($this->at10R / 2) . "%");
		o(" | RUSH中 10R (大当たりストック3個目): 時短 3000回: " . ($this->at10R / 2) . "%");
		o(" | RUSH中 4R: 時短 " . $this->jitan . "回: " . $at4RP . "%");
		o("======================================");
		echo '<div id="text"></div>';
		echo '<img src="img/railgunimg.jpg"/><br/>';
		echo '<a href="https://github.com/FuckingCodeGenerator/P/blob/main/railgun.php" target="_blank">
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
							$num1 = 7;
						else
						{
							$nums = [1, 2, 3, 4, 5, 6, 8];
							$num1 = $nums[mt_rand(0, 6)];
						}
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
				$str = "[" . sprintf("%04d", $game) . "G] 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | [" . $num1 . $num3 . $num2 . "]";
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
		$str = "[" . sprintf("%04d", $game) . "G] 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | [" . $num1 . $num3 . $num2 . "]";
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
		$str = "[" . sprintf("%04d", $game) . "G] 持ち玉: " . $this->ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $this->ball * 4 . "円 | " . $nums[3] .  "回転/1k | [" . $nums[0] . $nums[2] . $nums[1] . "]";
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
				if (!isset($_POST["skip_normal"]) || $isAtari)
				{
					$nums = $this->printGame($isAtari, $game, $this->ball, $usedBall, $isRush);
					msleep(500);
				}
			}

			if ($isAtari)
			{
				msleep(1000);
				$this->overridePrint("大当");
				msleep(3000);
				if ($isRush)
				{
					$this->overridePrint("超 御坂美琴BONUS");
					msleep(3000);
					$this->enterRush($gameId, $game);
				}
				else
				{
					$this->overridePrint("御坂美琴BONUS");
					msleep(3000); 
					$this->bonus($this->normalBonusCount, "御坂美琴BONUS");
					msleep(2000);
					$this->rush(410, $gameId, 1, true, $game);
				}
			}
		}
	}

	private function bonus($count, $title = null)
	{
		$counted = 0;
		if ($title == null)
			$title = "[" . $count . "BONUS]";
		else
			$title = "[" . $title . "]";
		$count4R = $count % 1500 / 200;
		$count10R = 3 - $count4R;
		if ($count == $this->normalBonusCount)
			$count10R = 0;
		$currentBonus = 1;
		while ($counted < $count)
		{
			$this->ball--;
			if ($this->isInRush())
			{
				if ($count10R == 0)
					$increace = $this->shokyu4R;
				else
				{
					if ($currentBonus <= $count10R)
						$increace = $this->shokyu10R;
					else
						$increace = $this->shokyu4R;
				}
				$this->ball += $increace;
				$counted += $increace;
				if ($count10R > 0 && $counted % 1500 == 0)
					$currentBonus++;
			}
			$str = $title . " 持ち玉: " . $this->ball . "玉 | " . $counted . "/" . $count . "pt";
			$this->overridePrint($str);
			msleep(100);
		}
		msleep(2000);
	}

	/**
	 * RUSH 突入
	 *
	 * @param int $gameId
	 * @param int $game
	 * @return void
	 */
	private function enterRush($gameId, $game)
	{
		$this->bonus($this->normalBonusCount, "超 御坂美琴BONUS");
		msleep(500);
		$this->rush(410, $gameId, 1, false, $game);
	}

	private function rush($counted, $gameId, $rushCount, $isJitan, $game, $jitan = null)
	{
		if ($jitan == null)
			$jitan = $this->jitan;
		$rushName = $isJitan ? "CHALLENGE" : "EXTRA";
		if ($rushCount == 1)
			$this->overridePrint("超電磁砲RUSH " . $rushName . " 100回 突入");
		else
			$this->overridePrint("超電磁砲RUSH EXTRA 100回 継続");
		msleep(2000);
		$atArray = [];
		$this->initAtariArray($atArray, $this->acJitan);
		for ($i = $jitan; $i >= 0; $i--)
		{
			if (!$this->isInRush())
			{
				$i++;
				$this->ball--;
				continue;
			}
			$rand = $this->genRand();
			$isAtari = $this->isAtari($atArray, $rand);
			if ($isJitan && $isAtari)
				$isAtari = mt_rand(1, 1000) <= $this->denchuLongP * 100 ? true : false;
			if ($isAtari)
			{
				$nums = [2, 4, 6, 8];
				$num1 = $nums[mt_rand(0, 3)];
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
			$remain = $jitan == $this->extraJitan ? 100 : $i;
			$str = "[超電磁砲RUSH " . $rushName . "] 残り" . $remain . "回 | TOTAL " . sprintf("%05d", $counted) . " | [" . $num1 . $num2 . $num3 . "]";
			$this->overridePrint($str);

			if ($isAtari)
			{
				msleep(2000);
				$totalBonus = 0;
				$extraRush = false;
				for ($k = 0; $k < 3; $k++)
				{
					if (mt_rand(1, 100) <= $this->at10R)
					{
						$totalBonus += 1500;
						if ($k == 2 && mt_rand(1, 100) <= ($this->at10R / 2))
							$extraRush = true;
					}
					else
						$totalBonus += 200;
				}
				if ($extraRush)
				{
					msleep(1500);
					$this->overridePrint("超電磁フリーズZONE", true);
					msleep(3000);
				}
				switch ($totalBonus)
				{
					case 600:	$textBonusC = 650; $title = "600 BONUS"; break;
					case 1900:	$textBonusC = 2000; $title = "最強 2000 無敵 BONUS"; break;
					case 3200:	$textBonusC = 3350; $title = "超 3000 最強無敵 BONUS"; break;
					case 4500:	$textBonusC = 4700; $title = "MAX 4000 最強無敵 BONUS"; break;
				}
				$this->bonus($totalBonus, $title);
				$counted += $textBonusC;
				$nextJitanC = $extraRush ? $this->extraJitan : $this->jitan;
				$this->rush($counted, $gameId + 1, $rushCount + 1, false, $game, $nextJitanC);
				return;
			}
			msleep(800);
		}
		$this->overridePrint("超電磁砲RUSH 終了", true);
		msleep(1000);
		$this->overridePrint("BONUS x " . $rushCount . " TOTAL " . sprintf("%05d", $counted) . " pt");
		msleep(2000);
		$this->updateData("railgun", $game, $rushCount, $counted, !$isJitan);
		$this->start($gameId + 1);
	}
}

o("Initializing. Please wait...");

$railgun = new Railgun(274, 963, 80, 20, 41.7, 7, 100, 0);
$railgun->onInit();
$railgun->start(0);