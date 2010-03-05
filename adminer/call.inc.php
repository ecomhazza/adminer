<?php
$PROCEDURE = $_GET["call"];
page_header(lang('Call') . ": " . h($PROCEDURE), $error);

$routine = routine($PROCEDURE, (isset($_GET["callf"]) ? "FUNCTION" : "PROCEDURE"));
$in = array();
$out = array();
foreach ($routine["fields"] as $i => $field) {
	if (substr($field["inout"], -3) == "OUT") {
		$out[$i] = "@" . idf_escape($field["field"]) . " AS " . idf_escape($field["field"]);
	}
	if (!$field["inout"] || substr($field["inout"], 0, 2) == "IN") {
		$in[] = $i;
	}
}

if (!$error && $_POST) {
	$call = array();
	foreach ($routine["fields"] as $key => $field) {
		if (in_array($key, $in)) {
			$val = process_input($field);
			if ($val === false) {
				$val = "''";
			}
			if (isset($out[$key])) {
				$connection->query("SET @" . idf_escape($field["field"]) . " = $val");
			}
		}
		$call[] = (isset($out[$key]) ? "@" . idf_escape($field["field"]) : $val);
	}
	if (!$connection->multi_query((isset($_GET["callf"]) ? "SELECT" : "CALL") . " " . idf_escape($PROCEDURE) . "(" . implode(", ", $call) . ")")) {
		echo "<p class='error'>" . error() . "\n";
	} else {
		do {
			$result = $connection->store_result();
			if (is_object($result)) {
				select($result);
			} else {
				echo "<p class='message'>" . lang('Routine has been called, %d row(s) affected.', $connection->affected_rows) . "\n";
			}
		} while ($connection->next_result());
		if ($out) {
			select($connection->query("SELECT " . implode(", ", $out)));
		}
	}
}
?>

<form action="" method="post">
<?php
if ($in) {
	echo "<table cellspacing='0'>\n";
	foreach ($in as $key) {
		$field = $routine["fields"][$key];
		echo "<tr><th>" . h($field["field"]);
		$value = $_POST["fields"][$key];
		if ($value != "" && ereg("enum|set", $field["type"])) {
			$value = intval($value);
		}
		input($field, $value, (string) $_POST["function"][$name]); // param name can be empty
		echo "\n";
	}
	echo "</table>\n";
}
?>
<p>
<input type="hidden" name="token" value="<?php echo $token; ?>">
<input type="submit" value="<?php echo lang('Call'); ?>">
</form>
