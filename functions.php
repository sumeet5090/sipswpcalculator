<?php
declare(strict_types=1);

// Format numbers in Indian Standard Notation (Lakhs/Crores)
function formatInr(float|int $num): string
{
	$num = round($num);
	$decimal = (string) ($num - floor($num));
	$money = floor($num);
	$length = strlen((string) $money);
	$delimiter = '';

	$money = (string) $money;

	if ($length <= 3) {
		$delimiter = $money;
	} else {
		$lastThree = substr($money, -3);
		$restUnits = substr($money, 0, -3); // extracts the last three digits
		$restUnits = (strlen($restUnits) % 2 == 1) ? "0" . $restUnits : $restUnits; // explodes the remaining digits in 2's formats, adds a zero in the front to make meaninful.
		$firstPart = '';
		$exploded = str_split($restUnits, 2);
		foreach ($exploded as $key => $value) {
			$firstPart .= (int) $value . ",";
		}
		$delimiter = $firstPart . $lastThree;
	}

	return "₹ " . $delimiter;
}
?>