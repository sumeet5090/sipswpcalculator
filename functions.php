<?php
declare(strict_types=1);

// Format numbers in USD with millions notation
function formatInr(float|int $num): string
{
	$absNum = abs($num);
	if ($absNum >= 1000000) { // 1 Million
		return '$' . round($num / 1000000, 2) . 'M';
	} else {
		return '$' . number_format(round($num), 0);
	}
}
?>
