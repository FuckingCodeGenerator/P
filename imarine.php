<?php
require_once 'pbase.php';

/**
 * PAスーパー海物語 IN 沖縄5 with アイマリン
 */
class IMarine extends PachinkoBase implements IPachinko
{
    const UNIQUE_ID = "imarine";
    const ST_5R  = 0;
    const ST_10R = 1;

	function __construct($atariCount, $atariCountST, $ar5P45, $ar5P20, $ar10P, $st, $ball)
	{
        $this->atariCount	= $atariCount;
        $this->atariCountST = $atariCountST;
		$this->ar10P	    = $ar10P;
        $this->ar5P45       = $ar5P45;
        $this->ar5P20       = $ar5P20;
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

    private $ar5P20;
    private $ar5P45;
	private $ar10P;
	private $st;
	private $atariCount;
    private $atariCountST;
	private $returnBall = 3;

    public function onInit()
	{
		$this->color("EEE409", "000000");
		echo '<div id="startup"></div>';
		echo '<div style="background-color:#ffffff; width: 1000px;"><canvas id="graph"></canvas></div>';
		$this->putGraph(self::UNIQUE_ID);
		echo '<a id="data"></a>';
		$this->putData(self::UNIQUE_ID);

		$this->atariCount = $this->atariCount;
		$this->initAtariArray($atariArray, $this->atariCount);
		$this->atariArray = $atariArray;
		
		o("\nDone.");
		o("\n======================================");
		o(" | PAスーパー海物語 IN 沖縄5 with アイマリン");
		o(" | 大当たり確立: 1/" . round(self::ARRAY_SIZE / $this->atariCount, 2) . " -> 1/" . round(self::ARRAY_SIZE / $this->atariCountST, 2));
		o(" | ST 突入率: 100%");
		o(" | ST 継続率: " . (1 - (pow((1 - 1 / (self::ARRAY_SIZE / $this->atariCountST)), $this->st))) * 100 . "%");
		o(" | ラウンド: 5R or 10R");
		o(" | 出玉: 550 or 1100");
		o(" | 5R: ST" . $this->st . " + 時短20回: " . $this->ar5P20 . "% (時短引き戻し率: " . (1 - (pow((1 - 1 / (self::ARRAY_SIZE / $this->atariCount)), 20))) * 100 . "%)");
		o(" | 5R: ST" . $this->st . " + 時短45回: " . $this->ar5P45 . "% (時短引き戻し率: " . (1 - (pow((1 - 1 / (self::ARRAY_SIZE / $this->atariCount)), 45))) * 100 . "%)");
		o(" | 10R: ST" . $this->st . " + 時短95回: " . $this->ar10P . "% (時短引き戻し率: " . (1 - (pow((1 - 1 / (self::ARRAY_SIZE / $this->atariCount)), 95))) * 100 . "%)");
		o("======================================");
		echo '<div id="text"></div>';
		echo '<img src="img/' . self::UNIQUE_ID . 'img.jpg"/><br/>';
		echo '<a href="https://github.com/FuckingCodeGenerator/P/blob/main/' . self::UNIQUE_ID . '.php" target="_blank">
				<img src="../GithubLogo.png" alt="GitHubでソースコードを見る"/>
			</a>';
	}

    private function printGame($isAtari, $game, $ball, $usedBall, $round)
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
						if ($round <= $this->ar10P)
                        {
                            $num1 = "M";
                        }
                        else if ($round <= ($this->ar10P + $this->ar5P45))
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
						$num2 = ($num1 == "M") ? "X" : $num1;
					if (!$reach && $num2 == $num1)
						$num2 = $this->getNumber($num2);
				}
				$num3 = $this->getNumber();
				$str = "(" . $game . "回転) 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | (" . $num1 . $num3 . $num2 . ")";
				if ($reach && $l == 2 && $i == 9)
				{
					if (!$skip)
					{
						$i = 0;
						$skip = true;
					}
				}
				if ($reach && $l == 2)
					$str .= " (リーチ)";
				$this->overridePrint($str);
				msleep(10);
			}
		}

		if ($isAtari)
			$num3 = ($num1 == "M") ? "A" : $num1;
		else
		{
			do
			{
				$num3 = $this->getNumber();
			} while ($num3 == $num2);
		}
		$str = "(" . $game . "回転) 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | (" . $num1 . $num3 . $num2 . ")";
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
		$str = "(" . $game . "回転) 持ち玉: " . $this->ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $this->ball * 4 . "円 | " . $nums[3] .  "回転/1k | (" . $nums[0] . $nums[2] . $nums[1] . ")";
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
                $roundR = mt_rand(1, 100);
				$nums = $this->printGame($isAtari, $game, $this->ball, $usedBall, $roundR);
				msleep(500);
			}

			if ($isAtari)
			{
				msleep(1000);
				$this->overridePrint("大当", true);
				msleep(3000);
				if ($roundR <= $this->ar10P)
				{
					$this->overridePrint("アイマリンBONUS", true);
					msleep(2000);
                    $this->overridePrint("M A X", true);
                    msleep(2000);
					$this->enterRush(1100, $gameId, $game, $roundR);
				}
                else
                    $this->enterRush(550, $gameId, $game, $roundR);
			}
		}
	}

	private function bonus($count, $round, $total)
	{
		$counted = 0;
        if ($round <= $this->ar10P)
            $title = "アイマリンBONUS";
        else if ($round <= ($this->ar10P + $this->ar5P45))
            $title = "SUPER LUCKY";
        else
            $title = "LUCKY";
		while ($counted < $count)
		{
			$this->ball--;
			if ($this->isInRush())
			{
				$this->ball += 11;
				$counted += 11;
			}
			$str = "[" . $title . "] 持ち玉: " . $this->ball . "玉 | " . sprintf("%05d", $total + $counted) . " | " . $counted . "/" . $count;
			$this->overridePrint($str);
			msleep(100);
		}
		msleep(2000);
	}

	private function enterRush($bonusCount, $gameId, $game, $round)
	{
		$this->bonus($bonusCount, $round, 0);
		msleep(500);
		$this->rush($bonusCount, $gameId, 1, $game, $round);
	}

	private function rush($counted, $gameId, $rushCount, $game, $round)
	{
		msleep(2000);
		$atArray = [];
        if ($round <= $this->ar10P)
            $jitan = 95;
        else if ($round <= ($this->ar10P + $this->ar5P45))
            $jitan = 45;
        else
            $jitan = 20;
		$this->initAtariArray($atArray, $this->atariCountST);
		for ($c = $this->st + $jitan; $c >= 0; $c--)
		{
            $isST = $c - $jitan >= 0;
            $skip = false;
			$rand = $this->genRand();
            $roundR = mt_rand(1, 100);
            $isAtari = $this->isAtari(($isST ? $atArray : $this->atariArray), $rand);
            $reach = $isAtari || ($isST ? mt_rand(1, 3) == 1 : $this->isReach());
            $bonus = 550;
            for ($l = 0; $l < 3; $l++)
            {
                for ($i = 0; $i < ($isST ? 100 : 5); $i++)
                {
                    if ($l == 0)
                    {
                        $num1 = $this->getNumber();
                        if ($i == ($isST ? 99 : 4) && $isAtari)
                        {
                            if ($roundR <= $this->ar10P)
                            {
                                $num1 = "M";
                                $bonus = 1100;
                            }
                            else if ($roundR <= ($this->ar10P + $this->ar5P45))
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
                            $num2 = ($num1 == "M") ? "X" : $num1;
                        if (!$reach && $num2 == $num1)
                            $num2 = $this->getNumber($num2);
                    }
                    $num3 = $this->getNumber();
                    $str = "(残り" . ($isST ? ($c - $jitan) : $c) . "回) (" . $num1 . $num3 . $num2 . ")";
                    if ($reach && $l == 2 && $i == ($isST ? 99 : 4))
                    {
                        if (!$skip)
                        {
                            $i = 0;
                            $skip = true;
                        }
                    }
                    if ($reach && $l == 2)
                        $str .= " (リーチ)";
                    $this->overridePrint($str);
                    msleep(10);
                }
            }
    
            if ($isAtari)
                $num3 = ($num1 == "M") ? "A" : $num1;
            else
            {
                do
                {
                    $num3 = $this->getNumber();
                } while ($num3 == $num2);
            }
    
            $str = "(残り" . ($isST ? ($c - $jitan) : $c) . "回) (" . $num1 . $num3 . $num2 . ")";
            $this->overridePrint($str);
            if ($isAtari)
            {
                msleep(2000);
                $this->bonus($bonus, $roundR, $counted);
                $counted += $bonus;
                $this->rush($counted, $gameId + 1, $rushCount + 1, $game, $roundR);
                return;
            }
            msleep(($isST ? 2000 : 650));
		}
        msleep(1500);
		$this->updateData(self::UNIQUE_ID, $game, $rushCount, $counted, true);
		$this->start($gameId + 1);
	}
}

o("Initializing. Please wait...");

$imarine = new IMarine(656, 6560, 57, 33, 10, 5, 0);
$imarine->onInit();
$imarine->start(0);