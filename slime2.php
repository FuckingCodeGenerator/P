<?php
require_once 'pbase.php';

/**
 * P 転生したらスライムだった件2
 */
class Slime2 extends PachinkoBase implements IPachinko
{
    const ST_3R  = 0;
    const ST_10R = 1;

	function __construct($atariCount, $atariCountInRush, $rush, $ball)
	{
		$this->atariCount		= $atariCount;
		$this->atariCountInRush	= $atariCountInRush;
		$this->rush		    = $rush;
		$this->ball		    = $ball;
	}

	/**
	 * 当たり数字一覧
	 *
	 * @var array
	 */
	private $atariArray = [];

	private $ball;

	private $atariCountInRush;
	private $rush;
    private $rush10R;
    private $totalRush;
	private $st = 0xffff;
	private $atariCount;
	private $returnBall = 1;
	private $normalBonusCount = 1500;

    public function onInit()
	{
		$this->color("000000");
		echo '<div id="startup"></div>';
		echo '<div style="background-color:#ffffff; width: 1000px;"><canvas id="graph"></canvas></div>';
		$this->putGraph("eva");
		echo '<a id="data"></a>';
		$this->putData("eva");

		$this->initAtariArray($atariArray, $this->atariCount);
		$this->atariArray = $atariArray;
		
		o("\nDone.");
		o("\n======================================");
		o(" | P 転生したらスライムだった件2");
		o(" | 大当たり確立: 1/" . round(self::ARRAY_SIZE / $this->atariCount, 2));
        o(" | 小当たり確率: 1/1.0");
		o(" | RUSH 突入率: " . $this->rush . "%");
        o(" | 転落大当たり確立: 1/" . round(self::ARRAY_SIZE / $this->atariCountInRush, 2));
        o(" | 小当たり時獲得出玉: 15");
		o(" | ラウンド: 0 or 10R");
		o(" | 出玉: 0 or 1500");
		o(" | 通常時 10R: 時短65535回: " . $this->rush . "%");
		o(" | 通常時 10R: 時短0回: " . (100 - $this->rush) . "%");
		o(" | RUSH中 0R: 時短0回: 100%");
		o("======================================");
		echo '<div id="text"></div>';
		echo '<img src="img/slime2img.jpg"/><br/>';
		echo '<a href="https://github.com/FuckingCodeGenerator/P/blob/main/slime2.php" target="_blank">
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
                        {
							$nums = [1, 3, 5, 7];
                            $num1 = $nums[mt_rand(0, 3)];
                        }
						else
                        {
							$nums = [2, 4, 6, 8];
                            $num1 = $nums[mt_rand(0, 3)];
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
				$str = "[" . $game . "GAME] 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | [" . $num1 . $num3 . $num2 . "]";
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
		$str = "[" . $game . "GAME] 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | [" . $num1 . $num3 . $num2 . "]";
		$this->overridePrint($str);

		return [$num1, $num2, $num3, $rate];
	}

	public function start($gameId)
	{
		set_time_limit(0);
		$game = 0;
		$usedBall = 0;
		$nums = [1, 3, 2, 0];
		$isAtari = false;
		$str = "[" . $game . "GAME] 持ち玉: " . $this->ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $this->ball * 4 . "円 | " . $nums[3] .  "回転/1k | [" . $nums[0] . $nums[2] . $nums[1] . "]";
		$this->overridePrint($str);
		while (1)
		{
			$this->ball--;
			$usedBall++;
			
			if ($this->isIn())
			{
				$game++;
				$this->ball += $this->returnBall;
				$isAtari = $this->isAtari($this->atariArray, $this->genRand(), true);
				$isRush = $this->isRush($this->rush);
				$this->printGame($isAtari, $game, $this->ball, $usedBall, $isRush);
				msleep(500);
			}

			if ($isAtari)
			{
				msleep(1000);
				$this->overridePrint("大当", true);
				msleep(3000);
				if ($isRush)
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
					$this->overridePrint("獲得: " . $this->normalBonusCount . " spt");
					msleep(1000);
					$this->updateData("slime2", $game, 1, $this->normalBonusCount, false);
					$this->start($gameId + 1);			
				}
			}
		}
	}

	private function bonus($count)
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
			$str = "[転スラBONUS] 持ち玉: " . $this->ball . "玉 | 獲得済: " . $counted . "/" . $count . " spt";
			$this->overridePrint($str);
			msleep(100);
		}
		msleep(2000);
	}

	private function enterRush($bonusCount, $gameId, $game)
	{
		$this->bonus($bonusCount);
		msleep(500);
		$this->rush($bonusCount, $gameId, 1, $game);
	}

	private function rush($counted, $gameId, $rushCount, $game)
	{
		if ($rushCount == 1)
			$this->overridePrint("転SRUSH 突入", true);
		else
			$this->overridePrint("転SRUSH 継続", true);
		msleep(2000);
		$arrayAt = [];
		$this->initAtariArray($arrayAt, $this->atariCountInRush);
		for ($i = $this->st; $i >= 0; $i--)
		{
            $this->ball--;
			$rand = $this->genRand();
            $isAtari = $this->isAtari($arrayAt, $rand);
			if ($isAtari)
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
			}
            $rushCount = ($this->st + 1) - $i;
            for ($k = 0; $k < 3; $k++)
            {
                if (!$isAtari)
                {
                    switch ($k)
                    {
                        case 0: $counted += 3; $this->ball += 3; break;
                        case 1: $counted += 8; $this->ball += 8; break;
                        case 2: $counted += 4; $this->ball += 4; break;
                    }
                }
                $str = "[SRUSH] TOTAL " . $counted . " spt | [" . $num1 . $num2 . $num3 . "] [" . $rushCount . "GAME]";
                $this->overridePrint($str);
                msleep(120);
            }
            if ($isAtari)
            {
                msleep(2000);
                $this->overridePrint("転SRUSH 終了", true);
                msleep(2500);
                $this->overridePrint("SRUSH x " . $rushCount . " | " . sprintf("%05d", $counted) . "pt", true);
                msleep(3000);
                $this->updateData("slime2", $game, $rushCount, $counted, true);
                $this->start($gameId + 1);        
                return;
            }
		}
	}
}

o("Initializing. Please wait...");

$slime = new Slime2(205, 103, 52, 3, 163, 0);
$slime->onInit();
$slime->start(0);