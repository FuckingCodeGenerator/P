<?php
require_once 'pbase.php';

/**
 * ぱちんこ ウルトラマンティガ 1500×84
 */
class Tiga extends PachinkoBase implements IPachinko
{
    const ST_3R  = 0;
    const ST_10R = 1;

	/**
	 * @param int $atariCount		確率分母
	 * @param int $koatariCount		RUSH中小当たり確率分母
	 * @param int $rush				RUSH突入率
	 * @param int $ball				初期持ち玉数
	 */
	function __construct($atariCount, $koatariCount, $rush, $ball)
	{
		$this->atariCount	= $atariCount;
		$this->koatariCount = $koatariCount;
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

	private $koatariCount;
	private $rush;
	private $jitan = 10000;
	private $atariCount;
	private $returnBall = 3;
	private $normalBonusCount = 450;

    public function onInit()
	{
		$this->color("A40000");
		echo '<div style="background-color:#ffffff; width: 1000px;"><canvas id="graph"></canvas></div>';
		$this->putGraph("tiga");
		echo '<a id="data"></a>';
		$this->putData("tiga");
		$this->initAtariArray($atariArray, $this->atariCount);
		$this->atariArray = $atariArray;

		$continueP = 0;
		$nextP = 1;
		$atariP = 1 / (self::ARRAY_SIZE / $this->koatariCount);
		$koatariP = 1 / (self::ARRAY_SIZE / $this->atariCount);
		for ($i = 0; $i < 500; $i++)
		{
			$continueP += $nextP * $atariP * (1 - $koatariP);
			$continueP += $nextP * $koatariP * (1 - pow((1 - $atariP), 5));
			$nextP *= (1 - $koatariP - $atariP * (1 - $koatariP));
		}
		
		o("\nDone.");
		o("\n======================================");
		o(" | ぱちんこ ウルトラマンティガ 1500×84");
		o(" | 大当たり確立: 1/" . round(self::ARRAY_SIZE / $this->atariCount, 2) . " -> 1/" . round(self::ARRAY_SIZE / $this->koatariCount, 2));
		o(" | RUSH中転落大当たり確立: 1/" . round(self::ARRAY_SIZE / $this->atariCount, 2));
		o(" | RUSH 突入率: " . $this->rush . "%");
		o(" | RUSH 継続率: " . $continueP * 100 . "%");
		o(" | ラウンド: 3R or 10R");
		o(" | 出玉: 450 or 1500");
		o(" | 通常時 10R: 時短次回まで: " . $this->rush . "%");
        o(" | 通常時 3R: 時短0回: " . (100 - $this->rush) . "%");
		o(" | RUSH中 小当たり: 10R: 時短次回まで: 100%");
        o(" | RUSH中 大当たり: 0R: 時短0回: 100%");
		o("======================================");
		echo '<div id="text"></div>';
		echo '<img src="img/tigaimg.jpg"/><br/>';
		echo '<a href="https://github.com/FuckingCodeGenerator/P/blob/main/tiga.php" target="_blank">
				<img src="../GithubLogo.png" alt="GitHubでソースコードを見る"/>
			</a>';
	}

    private function printGame($isAtari, $game, $ball, $usedBall, $isRush, $zanho = false)
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
							$nums = [1, 3, 5, 7];
                            $num1 = $nums[mt_rand(0, 3)];
                        }
						else
                        {
							$nums = [2, 4, 6, 8];
                            $num1 = $nums[mt_rand(0, 3)];
                        }
                        if ($zanho)
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
					$str = "[GAME " . $game . "] 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | [" . $num1 . $num3 . $num2 . "]";
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
			$str = "[GAME " . $game . "] 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | [" . $num1 . $num3 . $num2 . "]";
		$this->overridePrint($str);

		return [$num1, $num2, $num3, $rate];
	}

    /**
	 * ゲーム開始
	 *
	 * @return void
	 */
	public function start($gameId, $zanho = false, $counted = 0, $rushCount = 1)
	{
		set_time_limit(0);
		$game = 0;
		$usedBall = 0;
		$isAtari = false;

		if ($gameId != 0 && $game == 0 && $zanho)
		{
			$array10R = [];
			$this->initAtariArray($array10R, $this->koatariCount);
			for ($i = 4; $i >= 0; $i--)
			{
				$game++;
				$isAtari = $this->isAtari($array10R, $this->genRand());
				$this->printGame($isAtari, $game, 0, 1, false, true);
				msleep(1000);
				if ($isAtari)
				{
					$this->enterRush(1500, $gameId, $game, $counted, $rushCount);
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
				$this->printGame($isAtari, $game, $this->ball, $usedBall, $isRush);
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
					$this->enterRush(1500, $gameId, $game);
                }
                else
				{
					$this->overridePrint("通常確定");
					msleep(3000);
					$this->bonus($this->normalBonusCount, false);
					msleep(1000);
					$this->overridePrint("BONUS 終了", true);
					msleep(1500);
					$this->overridePrint("獲得: " . $this->normalBonusCount . "upt", true);
					msleep(1000);
					$this->updateData("tiga", $game, 1, $this->normalBonusCount, false);
					$this->start($gameId + 1);			
				}
			}
		}
	}

	private function bonus($count, $isHyper, $gl = false)
	{
		$counted = 0;
        $glitter = "";
        if ($gl)
            $glitter = "[PREMIUM GLITTER BONUS] ";
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
			$str = $glitter . "[ROUND " . $round . "] 持ち玉: " . $this->ball . "玉 | " . $counted . "upt";
			$this->overridePrint($str);
			msleep(100);
		}
		if ($isHyper)
			msleep(500);
		else
			msleep(2000);
	}

	private function enterRush($bonusCount, $gameId, $game, $counted = 0, $rushCount = 1)
	{
		$this->bonus($bonusCount, false);
		msleep(1000);
		$this->rush($bonusCount + $counted, $gameId, $rushCount, $game);
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
		$isHyper = $rushCount > 1;
		if ($isHyper)
			$rushName = "ウルトラ超光RUSH";
		else
			$rushName = "ウルトラバトルRUSH";
		if ($rushCount == 2)
		{
			msleep(3000);
			$this->overridePrint("ウルトラ超光RUSH 突入", true);
			msleep(2000);
		}
		if ($rushCount == 1)
			$this->overridePrint("ウルトラバトルRUSH 突入", true);
        else if ($rushCount != 2)
			$this->overridePrint("ウルトラ超光BONUS " . $rushCount . "回目", true);
		msleep(2000);
		$array0R = [];
		$arrayKoatari = [];
		$this->initAtariArray($array0R, $this->atariCount);
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
				$at0R = $this->isAtari($array0R, $rand);
				$koatari = $this->isAtari($arrayKoatari, $rand);
			} while ($at0R && $koatari);

			if ($koatari)
			{
                $num1 = 7;
                $num2 = 7;
                $num3 = 7;
			}
			else if ($at0R)
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
            $str = "[" . $rushName . "] [" . $num1 . $num2 . $num3 . "] | TOTAL " . $counted . " upt";
            $this->overridePrint($str);
            if ($koatari)
            {
				if ($isHyper)
                	msleep(500);
				else
					msleep(2500);
                $this->bonus(1500, $isHyper, !$isHyper);
                $counted += 1500;
                $this->rush($counted, $gameId + 1, $rushCount + 1, $game);
                return;
            }
			else if ($at0R)
			{
				msleep(3000);
				$this->overridePrint($rushName . " 終了");
				msleep(1500);
				$this->overridePrint("BONUS x " . $rushCount . " | " . sprintf("%05d", $counted) . " upt");
				msleep(3000);
				$this->updateData("tiga", $game, $rushCount, $counted, true);
				$this->start($gameId + 1, true, $counted, $rushCount);
			}
			if ($isHyper)
            	msleep(300);
			else
				msleep(900);
		}
	}
}

o("Initializing. Please wait...");

$tiga = new Tiga(205, 968, 50, 0);
$tiga->onInit();
$tiga->start(0);