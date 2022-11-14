<?php
require_once 'pbase.php';

/**
 * PFアイドルマスター ミリオンライブ! 39フェスver.
 */
class Idol extends PachinkoBase implements IPachinko
{
	const AT_TYPE_NONE = 0;
	const AT_TYPE_9R = 1;
	const AT_TYPE_NORMAL = 2;
	const AT_TYPE_3R = 3;

	function __construct($atariCount, $rush, $rushJ, $p9RJ, $p9R, $pNormal, $pNormalJ, $ball)
	{
		$this->atariCount		= $atariCount;
        $this->rush     = $rush;
        $this->rushJ    = $rushJ;
        $this->p9RJ     = $p9RJ;
        $this->pNormalJ = $pNormalJ;
        $this->pNormal  = $pNormal;
		$this->p9R		= $p9R;
		$this->ball		= $ball;
	}

	/**
	 * 当たり数字一覧
	 *
	 * @var array
	 */
	private $atariArray = [];

	private $ball;

	private $pTenraku;
    private $p9RJ;
    private $rushJ;
    private $rush;
    private $pNormalJ;
    private $pNormal;
	private $p9R;
	private $atariCount;
	private $returnBall = 3;
	private $bonus3R = 300;
	private $bonus9R = 900;

	public function onInit()
	{
		$this->color("FF5683");
		echo '<div style="background-color:#ffffff; width: 1000px;"><canvas id="graph"></canvas></div>';
		$this->putGraph("idol");
		echo '<a id="data"></a>';
		$this->putData("idol");

		$this->atariCount = $this->atariCount;
		$this->initAtariArray($atariArray, $this->atariCount);
		$this->atariArray = $atariArray;
		
		o("\nDone.");
		o("\n======================================");
		o(" | PFアイドルマスター ミリオンライブ! 39フェスver.");
		o(" | 大当たり確立: 1/" . round(self::ARRAY_SIZE / $this->atariCount, 2) . " -> 1/1.0");
        o(" | 遊タイム: 大当り後500回転消化で時短7回");
		o(" | RUSH 突入率: " . ($this->rush + $this->rushJ) . "% (上位RUSH " . $this->rushJ . "%含)");
		o(" | RUSH 継続率: " . (100 - $this->pNormal) . "%");
		o(" | 上位RUSH 継続率: " . (100 - $this->pNormalJ) . "%");
		o(" | ラウンド: 3R or 9R");
		o(" | 出玉: 300 or 900");
		o(" | 通常時 9R: 時短250回: " . $this->rushJ . "%");
		o(" | 通常時 3R: 時短7回: " . $this->rush . "%");
		o(" | 通常時 3R: 時短7回: " . (100 - ($this->rush + $this->rushJ)) . "%");
        o(" | 時短中 小当たり確率: 1/1.0");
        o(" | 時短中 大当たり確立: 1/1.0");
        o(" | 時短中 当たり合算確率: 1/1.0 (時短7, 250回中)");
		o(" | RUSH中 9R: 時短250回: " . $this->p9RJ . "%");
		o(" | RUSH中 3R: 時短7回: " . (100 - ($this->p9RJ + $this->pNormal)). "%");
		o(" | RUSH中 3R: 時短0回: " . $this->pNormal . "%");
		o(" | 上位RUSH中 9R: 時短250回: " . $this->p9R . "%");
		o(" | 上位RUSH中 3R: 時短250回: " . (100 - ($this->p9R + $this->pNormalJ)). "%");
		o(" | 上位RUSH中 3R: 時短0回: " . $this->pNormalJ . "%");
		o("======================================");
		echo '<div id="text"></div>';
        echo '<img src="img/idolimg.jpg"/><br/>';
		echo '<a href="https://github.com/FuckingCodeGenerator/P/blob/main/idol.php" target="_blank">
				<img src="../GithubLogo.png" alt="GitHubでソースコードを見る"/>
			</a>';
	}

	private function printGame($isAtari, $game, $ball, $usedBall, $isRush, $isRush9R)
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
                        if ($isRush9R)
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
				if ($game > 450)
					$str = "{39フェスまで 残り" . (500 - $game) . "G} 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | {" . $num1 . $num3 . $num2 . "}";
				else
					$str = "{GAME " . $game . "} 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | {" . $num1 . $num3 . $num2 . "}";
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
		if ($game > 450)
			$str = "{39フェスまで 残り" . (500 - $game) . "G} 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | {" . $num1 . $num3 . $num2 . "}";
		else
			$str = "{GAME " . $game . "} 持ち玉: " . $ball . "玉 | 消費玉: " . $usedBall . "玉 | 所持金: " . $ball * 4 . "円 | " . $rate .  "回転/1k | {" . $num1 . $num3 . $num2 . "}";
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
				$isRush = $this->isRush($this->rush);
                $isRush9R = $this->isRush($this->rushJ);
				if ($game == 501)
				{
					msleep(1000);
					$this->rush(0, $gameId, 1, 500, false, true);
					return;
				}
				if (!isset($_POST["skip_normal"]) || $isAtari)
				{
					$this->printGame($isAtari, $game, $this->ball, $usedBall, $isRush, $isRush9R);
					msleep(500);
				}
			}

			if ($isAtari)
			{
				msleep(1000);
				$this->overridePrint("大当");
                msleep(3000);
				if ($isRush9R)
					$this->enterRush($gameId, $game, true);
				else if ($isRush)
					$this->enterRush($gameId, $game, false);
				else
				{
					$this->overridePrint("通常確定");
					msleep(3000);
					$this->bonus(0, $this->bonus3R);
					msleep(1000);
					$this->overridePrint("BONUS 終了");
					$this->overridePrint("獲得: " . $this->bonus3R . "ありがとう");
					msleep(2000);
					$this->updateData("idol", $game, 1, $this->bonus3R, false);
					$this->start($gameId + 1);			
				}
			}
		}
	}

	private function bonus($balls, $count)
	{
		$counted = 0;
		while ($counted < $count)
		{
			$this->ball--;
			if ($this->isInRush())
			{
				$this->ball += 10;
				$counted += 10;
			}
			$str = "{" . $count . "BONUS} " . ($balls + $counted) . "ありがとう | " . $counted . "/" . $count . "ありがとう";
			$this->overridePrint($str);
			msleep(100);
		}
		msleep(1000);
	}

	private function enterRush($gameId, $game, $chou39)
	{
		$bonus = $chou39 ? $this->bonus9R : $this->bonus3R;
		$this->bonus(0, $bonus);
		msleep(500);
		$this->rush($bonus, $gameId, 1, $game, $chou39, true);
	}

	private function rush($counted, $gameId, $rushCount, $game, $chou39, $firstTime)
	{
        if (!$chou39)
            $name = "[熱狂度 80%] 39フェス";
        else
            $name = "[熱狂度 94%] 超 39フェス";
		if ($firstTime)
			$this->overridePrint($name . " 突入", true);
		else
			$this->overridePrint($name . " 継続", true);
		msleep(2000);
		$atariType = self::AT_TYPE_NONE;
        if ($chou39)
        {
            $bRand = mt_rand(1, 100);
            if ($bRand <= $this->p9R)
                $atariType = self::AT_TYPE_9R;
            else if ($bRand <= ($this->p9R + (100 - ($this->p9R + $this->pNormalJ))))
                $atariType = self::AT_TYPE_3R;
            else
                $atariType = self::AT_TYPE_NORMAL;
        }
        else
        {
            $bRand = mt_rand(1, 100);
            if ($bRand <= $this->p9RJ)
                $atariType = self::AT_TYPE_9R;
            else if ($bRand <= ($this->p9RJ + (100 - ($this->p9RJ + $this->pNormal))))
                $atariType = self::AT_TYPE_3R;
            else
                $atariType = self::AT_TYPE_NORMAL;
        }
        
        $firstTime = false;
        if ($atariType == self::AT_TYPE_NORMAL)
            $str = "アンコールボーナス";
        else
            $str = "ピーンときた!";
        $this->overridePrint($str, true);
        msleep(2000);
        if ($atariType == self::AT_TYPE_9R && !$chou39)
        {
            msleep(1000);
            $this->overridePrint("超 39フェス 開催決定!!!", true);
            $firstTime = true;
			$chou39 = true;
            msleep(3000);
        }
        $bonusCount = 0;
        switch ($atariType)
        {
            case self::AT_TYPE_9R: $bonusCount = 900; break;
            case self::AT_TYPE_NORMAL:
            case self::AT_TYPE_3R:
                    $bonusCount = 300;
                    break;
        }
        $this->bonus($counted, $bonusCount);
        $counted += $bonusCount;
        if ($atariType == self::AT_TYPE_NORMAL)
        {
            $this->overridePrint("RUSH 終了", true);
            msleep(1500);
            $this->overridePrint("[LIVE REPORT] " . $rushCount . "公演 | SCORE: " . $counted . "ありがとう", true);
            msleep(3000);
            $this->updateData("idol", $game, $rushCount, $counted, true);
            $this->start($gameId + 1);
            return;
        }
        $this->rush($counted, $gameId, $rushCount + 1, $game, $chou39, $firstTime);
	}
}

o("Initializing. Please wait...");

$idol = new idol(328, 50, 1, 22, 22, 20, 6, 0);
$idol->onInit();
$idol->start(0);