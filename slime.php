<?php
require_once 'pbase.php';

/**
 * P 転生したらスライムだった件
 */
class Slime extends PachinkoBase implements IPachinko
{
	const AT_TYPE_NONE = 0;
	const AT_TYPE_20R = 1;
	const AT_TYPE_NORMAL = 2;
	const AT_TYPE_10R = 3;

	function __construct($atariCount, $atariCountRush, $st, $stJ, $p20RJ, $p20R, $pNormal, $pTenraku, $ball)
	{
		$this->atariCount		= $atariCount;
		$this->atariCountRush	= $atariCountRush;
        $this->p20RJ    = $p20RJ;
        $this->pNormal  = $pNormal;
        $this->pTenraku = $pTenraku;
		$this->p20R		= $p20R;
		$this->st		= $st;
        $this->stJ      = $stJ;
		$this->ball		= $ball;
	}

	/**
	 * 当たり数字一覧
	 *
	 * @var array
	 */
	private $atariArray = [];

	private $ball;

	private $atariCountRush;
	private $pTenraku;
    private $p20RJ;
    private $pNormal;
	private $st;
    private $stJ;
	private $p20R;
	private $atariCount;
	private $returnBall = 1;
	private $normalBonusCount = 300;

	public function onInit()
	{
		echo '<div id="startup"></div>';
		$this->overridePrint(" - Initializing Atari array...", "startup");

		$this->atariCount = $this->atariCount;
		$this->initAtariArray($atariArray, $this->atariCount);
		$this->atariArray = $atariArray;

        $pRush = self::ARRAY_SIZE / $this->atariCountRush;
		
		o("\nDone.");
		o("\n======================================");
		o(" | (J) P 転生したらスライムだった件");
		o(" | 大当たり確立: 1/" . round(self::ARRAY_SIZE / $this->atariCount, 2) . " -> 1/" . round($pRush, 2));
		o(" | RUSH 突入率: 100%");
		o(" | RUSH 継続率: " . (1 - (pow((1 - 1 / $pRush), $this->st))) * 100 . "%");
        o(" | 上位RUSH 継続率: " . (1 - (pow((1 - 1 / $pRush), $this->stJ))) * 100 . "%");
		o(" | ラウンド: 2R or 10R");
		o(" | 出玉: 300 or 1500 or 3000");
		o(" | 通常時 2R: ST" . $this->st . ": 100%");
		o(" | RUSH中 10R x 2: ST" . $this->stJ . ": " . $this->p20RJ . "%");
		o(" | RUSH中 10R: ST" . $this->st . ": " . (100 - ($this->p20RJ + $this->pNormal)) . "%");
		o(" | RUSH中 10R: ST0: " . $this->pNormal . "%");
		o(" | 上位RUSH中 10R x 2: ST" . $this->stJ . ": " . $this->p20R . "%");
		o(" | 上位RUSH中 10R: ST" . $this->stJ . ": " . (100 - ($this->p20R + $this->pTenraku)) . "%");
		o(" | 上位RUSH中 10R: ST" . $this->st . ": " . $this->pTenraku . "%");
		o("======================================");
		echo '<div id="text"></div>';
        echo '<img src="img/slimeimg.jpg"/><br/>';
		echo '<a href="https://github.com/FuckingCodeGenerator/P/blob/main/slime.php" target="_blank">
				<img src="../GithubLogo.png" alt="GitHubでソースコードを見る"/>
			</a>';
	}

	private function printGame($isAtari, $game, $ball, $usedBall)
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
						$num1 = mt_rand(1, 9);
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
				$str = "{" . $game . "G} 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | {" . $num1 . $num3 . $num2 . "}";
				if ($reach && $l == 2 && $i == 9)
				{
					if (!$skip)
					{
						$i = 0;
						$skip = true;
					}
				}
				if ($reach && $l == 2)
					$str .= " {リーチ}";
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
		$str = "{" . $game . "G} 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | {" . $num1 . $num3 . $num2 . "}";
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
		$isAtari = false;
		while (1)
		{
			$this->ball--;
			$usedBall++;
			
			if ($this->isIn())
			{
				$game++;
				$this->ball += $this->returnBall;
				$isAtari = $this->isAtari($this->atariArray, $this->genRand());
				$this->printGame($isAtari, $game, $this->ball, $usedBall);
				msleep(500);
			}

			if ($isAtari)
			{
				msleep(1000);
				$this->overridePrint("大当");
                msleep(3000);
                $this->enterRush($gameId);
			}
		}
	}

	private function bonus($count)
	{
		$counted = 0;
        if ($count == $this->normalBonusCount)
            $title = "リムル";
        if ($count == 1500)
            $title = "リムル=テンペスト";
        if ($count == 3000)
            $title = "魔王 リムル=テンペスト";
		while ($counted < $count)
		{
			$this->ball--;
			if ($this->isInRush())
			{
				$this->ball += 15;
				$counted += 15;
			}
			$str = "{" . $title . "BONUS} 持ち玉: " . $this->ball . "玉 | " . $counted . "/" . $count . "pt";
			$this->overridePrint($str);
			msleep(100);
		}
		msleep(2000);
	}

	/**
	 * RUSH 突入
	 *
	 * @param int $gameId
	 * @return void
	 */
	private function enterRush($gameId)
	{
		$this->bonus($this->normalBonusCount);
		msleep(500);
		$this->rush($this->normalBonusCount, $gameId, $this->st);
	}

	/**
	 * RUSH
	 *
	 * @param int $counted	獲得済み玉数
	 * @param int $gameId
	 * @return void
	 */
	private function rush($counted, $gameId, $st)
	{
        if ($st == $this->st)
            $name = "転生したらRUSHだった件";
        else
            $name = "転生したら超RUSHだった件";
		if ($counted == $this->normalBonusCount)
			$this->overridePrint($name . " 開幕");
		else
			$this->overridePrint($name . " 継続");
		msleep(2000);
		$arAtari = [];
		$atariType = self::AT_TYPE_NONE;
		$this->initAtariArray($arAtari, $this->atariCountRush);
		for ($i = $st; $i >= 0; $i--)
		{
            if (!$this->isInRush())
            {
                $i++;
                $this->ball--;
                continue;
            }
			$rand = $this->genRand();
			if ($this->isAtari($arAtari, $rand))
			{
                if ($st == $this->st)
                {
                    $bRand = mt_rand(1, 100);
                    if ($bRand <= $this->p20RJ)
                        $atariType = self::AT_TYPE_20R;
                    else if ($bRand <= ($this->p20RJ + (100 - ($this->p20RJ + $this->pNormal))))
                        $atariType = self::AT_TYPE_10R;
                    else
                        $atariType = self::AT_TYPE_NORMAL;
                }
                else
                {
                    $bRand = mt_rand(1, 100);
                    if ($bRand <= $this->p20R)
                        $atariType = self::AT_TYPE_20R;
                    else if ($bRand <= ($this->p20R + (100 - ($this->p20R + $this->pTenraku))))
                        $atariType = self::AT_TYPE_NORMAL;
                    else
                        $atariType = self::AT_TYPE_10R;
                }
				switch ($atariType)
				{
                    case self::AT_TYPE_20R: $num1 = 7; break;
                    case self::AT_TYPE_10R:
                        $nums = [1, 3, 5, 9];
                        $num1 = $nums[mt_rand(0, 3)];
                        break;
                    case self::AT_TYPE_NORMAL:
                        $nums = [2, 4, 6, 8];
                        $num1 = $nums[mt_rand(0, 3)];
                        break;
				}
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
			$str = "{" . $name . "} 残り" . $i . "回 | " . $counted . "pt | {" . $num1 . $num2 . $num3 . "}";
			$this->overridePrint($str);

			if ($atariType != self::AT_TYPE_NONE)
			{
				msleep(1000);
				$bonusCount = 0;
				switch ($atariType)
				{
					case self::AT_TYPE_20R: $bonusCount = 3000; break;
					case self::AT_TYPE_10R:
                    case self::AT_TYPE_NORMAL:
                         $bonusCount = 1500;
                         break;
				}
				$this->bonus($bonusCount);
				$counted += $bonusCount;
                if ($atariType == self::AT_TYPE_NORMAL)
                {
                    if ($st == $this->st)
                    {
                        $this->overridePrint("RUSH 終了");
                        $this->overridePrint("獲得: " . $counted . "pt");
                        msleep(2000);
                        $this->start($gameId + 1);                        
                        return;                        
                    }
                    else
                    {
                        $st = $this->st;
                        $this->rush($counted, $gameId + 1, $st);
                        return;
                    }
                }
                if ($atariType == self::AT_TYPE_20R)
                    $st = $this->stJ;
				$this->rush($counted, $gameId + 1, $st);
				return;
			}
            $sleepTime = ($st == $this->st) ? 1200 : 600;
			msleep($sleepTime);
		}
		$this->overridePrint("RUSH 終了");
		$this->overridePrint("獲得: " . $counted . "pt");
		msleep(2000);
		$this->start($gameId + 1);
	}
}

echo '<body bgcolor="#0186CB" text="#00000000"/>';

o("Initializing. Please wait...");

$slime = new Slime(205, 984, 80, 111, 25, 1, 25, 35, 0);
$slime->onInit();
$slime->start(0);