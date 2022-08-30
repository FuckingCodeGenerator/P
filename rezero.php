<?php
echo '<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>';

/**
 * 改行自動出力echo
 *
 * @param string $text
 * @return void
 */
function o($text)
{
	$text = str_replace("\n", "</br>", $text);
	echo $text . "</br>";
}

/**
 * Sleep thread
 *
 * @param int $milliseconds
 * @return void
 */
function msleep($milliseconds)
{
	usleep($milliseconds * 1000);
}

/**
 * Base of P
 */
class PachinkoBase
{
	/**
	 * 確率分母
	 */
	const ARRAY_SIZE = 0xffff;

	/**
	 * 当たり数字を生成
	 *
	 * @param int[] $atariArray	当たり配列
	 * @param int $atariCount	当たり数
	 * @return void
	 */
	public function initAtariArray(&$atariArray, $atariCount)
	{
		for ($i = 0; $i < $atariCount; $i++)
		{
			$atariArray[$i] = $this->genRand();
			$c = 0;
			for ($n = $i; $n >= 0; $n--)
			{
				if ($atariArray[$n] == $atariArray[$i])
					$c++;
			}
			if ($c != 1)
				$i--;
		}
	}

	/**
	 * 乱数生成
	 *
	 * @return integer
	 */
	public function genRand(): int
	{
		return mt_rand(1, self::ARRAY_SIZE);
	}

	public function getNumber($currentNum = -1): int
	{
		if ($currentNum < 0)
			$currentNum = mt_rand(1, 9);
		$ret = $currentNum + 1;
		if ($ret > 9)
			return 1;
		return $ret;
	}

	/**
	 * 試行
	 *
	 * @param int $probability	確率分母
	 * @return boolean
	 */
	public function tryRand($probability): bool
	{
		return mt_rand(1, $probability) == 1;
	}

	/**
	 * 玉が入賞したか (1/13)
	 *
	 * @return boolean
	 */
	public function isIn(): bool
	{
		return $this->tryRand(13);
	}

	/**
	 * 玉が入賞したか (4/5)
	 *
	 * @return boolean
	 */
	public function isInRush(): bool
	{
		return mt_rand(1, 10) > 2;
	}

	/**
	 * リーチか (ハズレ)
	 *
	 * @return boolean
	 */
	private function isReach(): bool
	{
		return $this->tryRand(7);
	}

	public function overridePrint($text, $id = 0)
	{
		if (is_int($id))
			$id = "text";
		echo "<script>$('#" . $id . "').text('" . $text . "');</script>";
		@ob_flush();
		@flush();
	}

	public function printGame($gameId, $isAtari, $game, $ball, $usedBall, $isRush)
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
	 * 当たりかどうか
	 *
	 * @param int[] $atariArray	当たり一覧
	 * @param int $rand			乱数
	 * @param boolean $force	強制大当たり
	 * @return boolean
	 */
	public function isAtari($atariArray, $rand, $force = false): bool
	{
		if ($force)
			return true;
		return in_array($rand, $atariArray);
	}

	/**
	 * RUSH 抽選
	 *
	 * @param int $pRush	突入率
	 * @return boolean
	 */
	public function isRush($pRush): bool
	{
		return mt_rand(1, 100) <= $pRush;
	}

	public function is20R($p20R): bool
	{
		return mt_rand(1, 100) <= $p20R;
	}
}

/**
 * Interface of Pachinko programs
 */
interface IPachinko
{
	/**
	 * 初期化処理
	 *
	 * @return void
	 */
	public function onInit();

	/**
	 * 打ち出し開始
	 *
	 * @param int $gameId
	 * @return void
	 */
	public function start($gameId);
}

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
		echo '<div id="startup"></div>';
		$this->overridePrint(" - Initializing Atari array...", "startup");

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
				$nums = $this->printGame($gameId, $isAtari, $game, $this->ball, $usedBall, $isRush);
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
					$this->enterRush($gameId);
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
	 * @return void
	 */
	private function enterRush($gameId)
	{
		$this->bonus($this->rushBonusCount);
		msleep(500);
		$this->rush($this->rushBonusCount, $gameId);
	}

	/**
	 * RUSH
	 *
	 * @param int $counted	獲得済み玉数
	 * @param int $gameId
	 * @return void
	 */
	private function rush($counted, $gameId)
	{
		if ($counted == $this->rushBonusCount)
			$this->overridePrint("RUSH 突入");
		else
			$this->overridePrint("RUSH 継続");
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
			$str = "[RUSH] 残り" . $i . "回 | " . $counted . "pt | [" . $num1 . $num2 . $num3 . "]";
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
					$this->rush($counted, $gameId + 1);
					return;
				}
				$this->bonus($bonusCount);
				$counted += $bonusCount;
				$this->rush($counted, $gameId + 1);
				return;
			}
			msleep(650);
		}
		$this->overridePrint("RUSH 終了");
		$this->overridePrint("獲得: " . $counted . "pt");
		msleep(2000);
		$this->start($gameId + 1);
	}
}

o("Initializing. Please wait...");

$reZero = new ReZero(205, 360, 92, 55, 144, 0.8, 0);
$reZero->onInit();
$reZero->start(0);