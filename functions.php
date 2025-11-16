<?php
declare(strict_types=1);

// Format numbers in Indian numbering system with Lakh/Cr suffix for large numbers
function formatInr(float|int $num): string
{
	$absNum = abs($num);
	if ($absNum >= 10000000) { // 1 Crore
		return '₹' . round($num / 10000000, 2) . ' Cr';
	} elseif ($absNum >= 100000) { // 1 Lakh
		return '₹' . round($num / 100000, 2) . ' L';
	} else {
		$numStr = (string) round($num);
		if (strlen($numStr) > 3) {
			$last3 = substr($numStr, -3);
			$rest = substr($numStr, 0, -3);
			$rest = preg_replace("/\\B(?=(\\d{2})+(?!\\d))/", ",", $rest);
			return '₹' . $rest . ',' . $last3;
		}
		return '₹' . $numStr;
	}
}
?>
