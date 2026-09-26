<?php
class theme_at {

	function table ($content, $width = '100%') {
		$out = '<table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" class="main" align="center"' . ($width !== false ? ' width="' . $width . '"' : '') . '>' . "\n";
		$out.= $content;
		$out.= "</table>\n";
		return $out;
	}

	function back ($href, $text = null) {
		return '<div align="center" style="margin:8px;"><a href="' . $href . '">' . ($text ?? lang('Back')) . '</a></div>' . "\n";
	}
	
	function error ($text) {
		return '<div align="center" style="margin:8px;color:red;"><b>' . $text . '</b></div>' . "\n";
	}

	function message ($text) {
		return '<div align="center" style="margin:8px;"><b>' . $text . '</b></div>' . "\n";
	}

	function select ($name, $values, $current = false, $multiple=false) {
		$out = '<select name="' . $name . '" class="input" '.($multiple ? ' multiple="multiple" ': '').' >' . "\n";
		foreach ($values as $key => $value) {
			$out.= '<option value="' . $key . '"' . ($key == $current ? ' selected' : '') . '>' . $value . '</option>' . "\n";
		}
		$out.= '</select>' . "\n";
		return $out;
	}

}