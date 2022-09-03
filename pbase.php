<?php
echo '<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>';
echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.js"></script>';

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
	private $color = "ffffff";
	private $textColor = "000000";

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
		for ($i = 0; $i < $atariCount; $i++) {
			$atariArray[$i] = $this->genRand();
			$c = 0;
			for ($n = $i; $n >= 0; $n--) {
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

	public function overridePrint($text, $twice = false, $id = 0, $html = false)
	{
		if (is_int($id))
			$id = "text";
		if ($html)
			echo "<script>$('#" . $id . "').html('" . $text . "');</script>";
		else
			echo "<script>$('#" . $id . "').text('" . $text . "');</script>";
		@ob_flush();
		@flush();
		if ($twice)
			$this->overridePrint($text, false, $id, $html);
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

	public function color($color, $textColor = "ffffff")
	{
		$this->color = $color;
		$this->textColor = $textColor;
		echo '<body bgcolor="#' . $color . '" text="#' . $textColor . '"/>';
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

	public function putData($machine)
	{
		$file = file_get_contents("data.json");
		$data = json_decode($file, true);
		if (!isset($data[$machine]))
			$mData = [[319, 1, 0], [219, 1, 0]];
		else
			$mData = $data[$machine];
		$totalBall = 0;
		$totalGame = 0;
		$totalK = 0;
		foreach ($mData as $key => $value) {
			$totalBall += $value[2];
			$totalGame += $value[0];
			$totalK += $value[1];
		}
		$str = "[機種データ]\n";
		$str .= " - 総回転数: " . $totalGame . "回転\n";
		$str .= " - 総出玉数: " . $totalBall . "個\n";
		$str .= " - 平均連荘回数: " . round($totalK / count($mData), 2) . "回\n";
		$str = str_replace("\n", "<br>", $str);
		$this->overridePrint($str, false, "data", true);
	}

	public function putGraph($machine)
	{
		if (!file_exists("data.json"))
			touch("data.json");
		$file = file_get_contents("data.json");
		$data = json_decode($file, true);
		if (!isset($data[$machine]))
			$mData = [[319, 1, 0], [219, 1, 0]];
		else
			$mData = $data[$machine];
		$games = "[";
		$labels = "[";
		foreach ($mData as $key => $value) {
			$games .= "" . $value[0] . ",";
			if ($value[1] == 1)
				$labels .= "['" . $value[0] . "回転', '単発'],";
			else
				$labels .= "['" . $value[0] . "回転', '" . $value[1] . "連', '出玉: " . $value [2] . "発'],";
		}
		$games = trim($games, ",");
		$labels = trim($labels, ",");
		$games .= "]";
		$labels .= "]";
		echo "<script>
		(function(){
			const data = {
				labels: " . $labels . ",
				datasets: [{
					label: 'データカウンター グラフ',
					backgroundColor: 'rgb(255,99,132,0)',
					borderColor: 'rgb(255,99,132)',
					data: " . $games . ",
					tension: 0,
					pointStyle: 'cross',
					borderWidth: 1.5
				}]
			};
			const settings = {
				type: 'line',
				data: data,
				options: {
					scales: {
						yAxes: [{
							ticks: {
							display: true,
							suggestedMin: 0,
							suggestedMax: 1000,
								stepSize: 100,
								beginAtZero: true
							}
						}]
					}			
				}
			};
			var chart = new Chart(
				$('#graph'),
				settings
			);
		})();
		</script>";
	}

	public function updateData($machine, $game, $kakuhen, $balls)
	{
		if (!file_exists("data.json"))
			touch("data.json");
		$file = file_get_contents("data.json");
		$data = json_decode($file, true);
		if (!isset($data[$machine]))
			$mData = [[319, 1, 0], [219, 1, 0]];
		else
			$mData = $data[$machine];
		array_unshift($mData, [$game, $kakuhen, $balls]);
		$games = "[";
		$labels = "[";
		$i = 0;
		foreach ($mData as $key => $value) {
			$games .= "" . $value[0] . ",";
			if ($value[1] == 1)
				$labels .= "['" . $value[0] . "回転', '単発'],";
			else
			$labels .= "['" . $value[0] . "回転', '" . $value[1] . "連', '出玉: " . $value [2] . "発'],";
			if ($i++ == 10)
				break;
		}
		$games = trim($games, ",");
		$labels = trim($labels, ",");
		$games .= "]";
		$labels .= "]";
		echo "<script>
		(function(){
			const data = {
				labels: " . $labels . ",
				datasets: [{
					label: 'データカウンター グラフ',
					backgroundColor: 'rgb(255,99,132,0)',
					borderColor: 'rgb(255,99,132)',
					data: " . $games . ",
					tension: 0,
					pointStyle: 'cross',
					borderWidth: 1.5
				}]
			};
			const settings = {
				type: 'line',
				data: data,
				options: {
					scales: {
						yAxes: [{
							ticks: {
							display: true,
							suggestedMin: 0,
							suggestedMax: 1000,
								stepSize: 100,
								beginAtZero: true
							}
						}]
					}			
				}
			};
			var chart = new Chart(
				$('#graph'),
				settings
			);
		})();
		</script>";
		$data[$machine] = $mData;
		file_put_contents("data.json", json_encode($data));
		$this->putData($machine);
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
