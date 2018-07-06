<?php

require_once __DIR__ . '/pytext-table.php';

function __parse_note_pattern($ptn_spec)
{
	$note_params = array();
	$note_params['type'] = 'f';
	$note_params['font'] = '';
	$note_params['size'] = '';
	$note_params['color'] = '';
	$note_params['raise'] = '';
	$note_params['ptn'] = '';

	do {
		$fields = array();
		if (preg_match('/^(\w+)(.*)/', $ptn_spec, $fields) <= 0)
			break;
		$ptn_spec = $fields[2];
		if ($fields[1] == 'footnote') {
			$note_params['type'] = 'f';
			$note_params['ptn'] = '\zynotetext{*}';
		} elseif ($fields[1] == 'gezhu') {
			$note_params['type'] = 'g';
			$note_params['ptn'] = '\gezhu{*}';
		} else {
			break;
		}
		while ($ptn_spec != '') {
			if (preg_match('/^\s*,\s*([^=,]+)(=([^=,]*))?(.*)/', $ptn_spec, $fields) <= 0)
				break;
			$ptn_spec = $fields[4];
			if ($fields[1] == 'font') {
				$note_params['font'] = $fields[3];
			} elseif ($fields[1] == 'size') {
				$note_params['size'] = $fields[3];
			} elseif ($fields[1] == 'color') {
				$note_params['color'] = $fields[3];
			} elseif ($fields[1] == 'raise') {
				$note_params['raise'] = $fields[3];
			}
		}
	} while (false);

	return $note_params;
}

function __ucs2_ord($c)
{
    return ord($c[0]) * 256 + ord($c[1]);
}

function __to_utf8($s)
{
    return mb_convert_encoding($s, 'UTF-8', 'UCS-2BE');
}

function __to_ucs2($s)
{
    return mb_convert_encoding($s, 'UCS-2BE', 'UTF-8');
}

function __split_ucs2_string($s)
{
    $nr = mb_strlen($s, 'UCS-2BE');
    $cs = array();
    for ($i=0; $i<$nr; $i++) {
        $cs[] = mb_substr($s, $i, 1, 'UCS-2BE');
    }
    return $cs;
}

function __is_char($s, $c)
{
    $t = __to_utf8($s);
    return $t == $c;
}

function __print_cmd(&$s, $len, $i)
{
    $c = $s[$i]; $c8 = __to_utf8($c);
	if ($c8 == '\\') {
		$text = $c8;
		$i++;
		while ($i < $len) {
            $c = $s[$i]; $c8 = __to_utf8($c);
			$n = __ucs2_ord($c);
            if ($n <= 32 || $n >= 256 || $c8 == '\\' || $c8 == '{' || $c8 == '}')
                break;
			$text .= $c8;
			$i++;
		}
		if ($text != '\\gezhu@oldpar') {
            echo $text;
        }
	}
	return $i;
}

function __print_embraced_text(&$s, $len, $i, $include_braces)
{
	$level = 0;
	while ($i < $len) {
        $c = $s[$i]; $c8 = __to_utf8($c);
		if ($c8 == '{') {
            if ($level > 0 || $include_braces)
                echo '{';
			$level++;
		} elseif ($c8 == '}') {
			$level--;
            if ($level > 0 || $include_braces)
                echo '}';
		} else {
			echo $c8;
		}
		$i++;
        if ($level == 0)
            break;
	}
	return $i;
}

function __print_text_attr($note_params)  
{
    $attr = '';
    if ($note_params['color'] != '')
        $attr .= "\\color{{$note_params['color']}}";
    if ($note_params['font'] != '')
        $attr .= "\\{$note_params['font']}";
    if ($note_params['size'] != '')
        $attr .= "\\{$note_params['size']}";
	echo $attr;
}

function __print_note_ptn_head($note_params)
{
    $fields = array();
    if (preg_match('/^(.*?)\*/', $note_params['ptn'], $fields) > 0) {
		echo $fields[1];
	} else {
		echo $note_params['ptn'];
	}
}

function __print_note_ptn_tail($note_params)
{
    $fields = array();
    if (preg_match('/^.*?\*(.*)/', $note_params['ptn'], $fields) > 0) {
		echo $fields[1];
    }
}

function __print_note_head($note_params)
{
    echo '{';
    if ($note_params['type'] == 'g') {
		__print_text_attr($note_params);
        echo '\\hspace{0.5ex}';
    } elseif ($note_params['type'] == 'f') {
        if ($note_params['raise'] != '')
            echo "\\raisebox{{$note_params['raise']}}{";
        echo '\\zynotemark';
        if ($note_params['raise'] != '')
            echo '}';
    }
	__print_note_ptn_head($note_params);
	if ($note_params['type'] == 'f') {
		__print_text_attr($note_params);
		echo '{}';
	}
}

function __print_note_tail($note_params)
{
    __print_note_ptn_tail($note_params);
    if ($note_params['type'] == 'g') {
        echo '\\hspace{0.125ex}';
    }
    echo '}';
}

function __parse_decoration(&$s, $len, $i)
{
	$py = '';
	$rs = '';

	$text = '';
	if ($i < $len) {
	    $c = $s[$i]; $c8 = __to_utf8($c);
		if ($c8 == '(') {
			$i++;
			while ($i < $len) {
	            $c = $s[$i]; $c8 = __to_utf8($c);
				if ($c8 == ')') {
					$i++;
					break;
				}
				$text .= $c8;
				$i++;
			}
		}
	}

	$ds = explode('/', $text);
	foreach ($ds as $d) {
		if (preg_match('/^[\d\-]/', $d) > 0) {
			$rs = $d;
			continue;
		}
		if (preg_match('/^[a-z]/', $d) > 0) {
			$py = $d;
			continue;
		}
	}

	return array($i, $py, $rs);
}

function zy($text, $grid = false, $note_spec = 'footnote')
{
    global $py_table;

	ob_start();

    $note_params = __parse_note_pattern($note_spec);

    $fields = array();
    $utext = mb_convert_encoding($text, 'UCS-2BE', 'UTF-8');
    $s = __split_ucs2_string($utext);
    $len = count($s);
	$i = 0;
	while ($i < $len) {
        $c = $s[$i]; $c8 = __to_utf8($c);
		if ($c8 == '\\') {
			$i = __print_cmd($s, $len, $i);
			continue;
        }
		if ($c8 == '{') {
			$i = __print_embraced_text($s, $len, $i, true);
			continue;
		}
		if ($c8 == '@') {
			$i++;
            $c = $s[$i]; $c8 = __to_utf8($c);
			if ($i < $len && $c8 == '{') {
				__print_note_head($note_params);
				$i = __print_embraced_text($s, $len, $i, false);
				__print_note_tail($note_params);
			} else {
				echo '@';
			}
			continue;
		}
        $n = __ucs2_ord($c);
		if (!empty($py_table[$n])) {
			$i++;
            list ($i, $py, $rs) = __parse_decoration($s, $len, $i);
            $prefix = $grid ? 'g' : '';
            $postfix = $py == '' ? '' : '*';
            if ($py == '') {
                $py = $py_table[$n];
                if (preg_match('/^(\S+)/', $py, $fields) > 0)
                    $py = $fields[1];
				if (preg_match('/^(.*)(\d)$/', $py, $fields) > 0 && $fields[2] == '5')
                    $py = $fields[1];
            }
            echo "\\{$prefix}zy$postfix";
            if ($rs != '') {
				$postfix = preg_match('/\d$/', $rs) > 0 ? 'ex' : '';
                echo "[$rs$postfix]";
            }
            echo "{\py$py}{{$c8}}";
		} else {
			$i++;
			echo $c8;
		}
	}

	$contents = ob_get_contents();

	ob_end_clean();

	return $contents;
}

function print_zy($text, $grid = false, $note_spec = 'footnote')
{
	echo zy($text, $grid, $note_spec);
}

function __get_phrase_chars($phrase)
{
	$s = preg_replace('/\\(.*?\\)/', '', $phrase);
	$us = mb_convert_encoding($s, 'UCS-2BE', 'UTF-8');
	return mb_strlen($us, 'UCS-2BE');
}

function __split_phrases($lines, $line_chars, $space_chars)
{
	$s = '';
	$nr = count($lines);
	for ($i=0; $i<$nr; $i++) {
		if ($i > 0)
			$s .= ' ';
		$s .= trim($lines[$i]);
	}
	$phrases = preg_split('/\\s+/', $s);

	$new_lines = array();
	$new_justs = array();
	$s = '';
	$chars = 0;
	foreach ($phrases as $phrase) {
		$padding = $chars == 0 ? 0 : $space_chars;
		$phrasechars = __get_phrase_chars($phrase);
		if ($chars + $padding + $phrasechars <= $line_chars) {
			$s .= ($padding == 0 ? '' : ' ') . $phrase;
			$chars += $padding + $phrasechars;
		} else {
			$new_lines[] = $s;
			$new_justs[] = $chars > $line_chars - (4 + $space_chars) ? true : false;
			$s = $phrase;
			$chars = $phrasechars;
		}
	}
	if ($s != '') {
		$new_lines[] = $s;
		$new_justs[] = $chars > $line_chars - (4 + $space_chars) ? true : false;
	}
	return array($new_lines, $new_justs);
}

function cphrases($p, $line_chars, $space_chars)
{
	$lines = explode("\n", $p);
	list ($lines, $justs) = __split_phrases($lines, $line_chars, $space_chars);
	$nr = count($lines);
	$text = "\\gzyni\\cz{%\n";
	$line_tail = "\\vspace{1ex}\\gzynl\n";
	for ($i=0; $i<$nr; $i++) {
		$s = trim($lines[$i]);
		if ($justs[$i])
			$text .= "\\makebox[\\textwidth][s]{" . zy($s, true) . "}$line_tail";
		else
			$text .= zy($s, true) . $line_tail;
	}
	$text .= "}\n\n";
	return $text;
}

function print_cphrases($p, $line_chars, $space_chars)
{
	echo cphrases($p, $line_chars, $space_chars);
}
