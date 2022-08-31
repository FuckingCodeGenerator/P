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
	public function isReach(): bool
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