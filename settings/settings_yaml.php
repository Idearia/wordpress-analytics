<?php
/**
 * Minimal YAML reader for the plugin's settings file.
 *
 * It exists only to keep reading a legacy settings/settings.yaml left on a
 * website, without any external library. It supports the subset of YAML used
 * by the settings file: nested mappings by indentation, comments, plain and
 * quoted scalars, and folded (>) or literal (|) text blocks. Anything else
 * (lists, anchors, inline collections...) makes it throw an exception, so that
 * the caller can fall back to the bundled JSON file.
 *
 * Part of WordPress Analytics:
 * https://github.com/coccoinomane/wordpress_analytics
 */

/**
 * Parse a YAML string into an array.
 *
 * @param String $yaml
 * @return Array
 * @throws Exception If the string uses unsupported YAML syntax.
 */
function wpan_parse_settings_yaml( $yaml ) {

	$lines = preg_split( '/\r\n|\r|\n/', (string) $yaml );

	// The line break ending the last line does not start a new one
	if ( count( $lines ) > 1 && '' === end( $lines ) ) {
		array_pop( $lines );
	}

	if ( isset( $lines[0] ) ) {
		$lines[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $lines[0] );
	}

	$index  = 0;
	$result = wpan_parse_settings_yaml_mapping( $lines, $index, 0 );

	if ( $index < count( $lines ) ) {
		throw new Exception( 'Unexpected indentation at line ' . ( $index + 1 ) );
	}

	return $result;
}

/**
 * Parse the mapping starting at line $index, made of the lines indented by at
 * least $min_indent spaces.
 *
 * @param Array $lines
 * @param Int   $index Current line, updated by reference.
 * @param Int   $min_indent
 * @return Array
 * @throws Exception If the lines use unsupported YAML syntax.
 */
function wpan_parse_settings_yaml_mapping( $lines, &$index, $min_indent ) {

	$result = [];
	$indent = null;
	$count  = count( $lines );

	while ( $index < $count ) {

		$line    = $lines[ $index ];
		$trimmed = trim( $line );

		// Skip blank lines, comments and document markers
		if ( '' === $trimmed || '#' === $trimmed[0] || '---' === $trimmed || '...' === $trimmed ) {
			++$index;
			continue;
		}

		$number = $index + 1;

		if ( preg_match( '/^ *\t/', $line ) ) {
			throw new Exception( "Tabs cannot be used for indentation at line $number" );
		}

		$line_indent = strlen( $line ) - strlen( ltrim( $line, ' ' ) );

		if ( null === $indent ) {
			if ( $line_indent < $min_indent ) {
				break;
			}
			$indent = $line_indent;
		}

		if ( $line_indent < $indent ) {
			break;
		}

		if ( $line_indent > $indent ) {
			throw new Exception( "Unexpected indentation at line $number" );
		}

		if ( ! preg_match( '/^(\'(?:[^\']|\'\')*\'|"(?:[^"\\\\]|\\\\.)*"|[^\s\'"#&*!|>%@`\[\]{},?-][^#]*?)\s*:(?:\s+(.*))?$/', $trimmed, $matches ) ) {
			throw new Exception( "Unsupported syntax at line $number" );
		}

		$key   = wpan_parse_settings_yaml_scalar( $matches[1], $number, true );
		$value = isset( $matches[2] ) ? wpan_strip_settings_yaml_comment( $matches[2] ) : '';

		if ( array_key_exists( $key, $result ) ) {
			throw new Exception( "Duplicate key '$key' at line $number" );
		}

		++$index;

		if ( '' === $value ) {

			// Either a nested mapping or an empty value
			$nested         = wpan_parse_settings_yaml_mapping( $lines, $index, $indent + 1 );
			$result[ $key ] = $nested ? $nested : null;

		} elseif ( preg_match( '/^([>|])([+-]?)$/', $value, $block ) ) {

			$result[ $key ] = wpan_parse_settings_yaml_block( $lines, $index, $indent, $block[1], $block[2] );

		} else {

			$result[ $key ] = wpan_parse_settings_yaml_scalar( $value, $number, false );

		}
	}

	return $result;
}

/**
 * Parse a folded (>) or literal (|) text block, made of the lines following
 * $index that are indented more than $parent_indent spaces.
 *
 * @param Array  $lines
 * @param Int    $index Current line, updated by reference.
 * @param Int    $parent_indent
 * @param String $style Either '>' or '|'.
 * @param String $chomping Either '', '-' or '+'.
 * @return String
 */
function wpan_parse_settings_yaml_block( $lines, &$index, $parent_indent, $style, $chomping ) {

	$block        = [];
	$block_indent = null;
	$count        = count( $lines );

	while ( $index < $count ) {

		$line = $lines[ $index ];

		if ( '' === trim( $line ) ) {
			$block[] = '';
			++$index;
			continue;
		}

		$line_indent = strlen( $line ) - strlen( ltrim( $line, ' ' ) );

		if ( $line_indent <= $parent_indent ) {
			break;
		}

		if ( null === $block_indent ) {
			$block_indent = $line_indent;
		}

		if ( $line_indent < $block_indent ) {
			break;
		}

		$block[] = rtrim( substr( $line, $block_indent ), "\r" );
		++$index;
	}

	// Trailing blank lines are kept only with the '+' chomping indicator
	$trailing = 0;
	while ( $block && '' === end( $block ) ) {
		array_pop( $block );
		++$trailing;
	}

	if ( ! $block ) {
		return '+' === $chomping ? str_repeat( "\n", $trailing ) : '';
	}

	if ( '|' === $style ) {

		$text = implode( "\n", $block );

	} else {

		// Fold: a line break becomes a space, unless it surrounds a blank or
		// a more indented line
		$text     = '';
		$previous = null;

		foreach ( $block as $line ) {
			if ( null === $previous ) {
				$text = $line;
			} elseif ( '' === $line ) {
				$text .= "\n";
			} elseif ( '' === $previous ) {
				$text .= $line;
			} elseif ( ' ' === $line[0] || ' ' === $previous[0] ) {
				$text .= "\n" . $line;
			} else {
				$text .= ' ' . $line;
			}
			$previous = $line;
		}
	}

	if ( '-' === $chomping ) {
		return $text;
	}

	return $text . ( '+' === $chomping ? str_repeat( "\n", $trailing + 1 ) : "\n" );
}

/**
 * Remove a trailing comment from an unquoted value.
 *
 * @param String $value
 * @return String
 */
function wpan_strip_settings_yaml_comment( $value ) {

	$value = trim( $value );

	if ( '' === $value || '#' === $value[0] ) {
		return '';
	}

	// Quoted values: the comment, if any, follows the closing quote
	if ( preg_match( '/^(\'(?:[^\']|\'\')*\'|"(?:[^"\\\\]|\\\\.)*")\s*(?:#.*)?$/', $value, $matches ) ) {
		return $matches[1];
	}

	return rtrim( preg_replace( '/\s+#.*$/', '', $value ) );
}

/**
 * Convert a YAML scalar to the matching PHP value.
 *
 * @param String $value
 * @param Int    $number Line number, for error messages.
 * @param Bool   $is_key Keys are always returned as strings or integers.
 * @return Mixed
 * @throws Exception If the value uses unsupported YAML syntax.
 */
function wpan_parse_settings_yaml_scalar( $value, $number, $is_key ) {

	$length = strlen( $value );

	if ( $length >= 2 && "'" === $value[0] && "'" === $value[ $length - 1 ] ) {
		return str_replace( "''", "'", substr( $value, 1, -1 ) );
	}

	if ( $length >= 2 && '"' === $value[0] && '"' === $value[ $length - 1 ] ) {
		$inner = substr( $value, 1, -1 );
		if ( preg_match( '/\\\\[^"\\\\\/ntr]/', $inner ) ) {
			throw new Exception( "Unsupported escape sequence at line $number" );
		}
		return strtr( $inner, [ '\\"' => '"', '\\\\' => '\\', '\\/' => '/', '\\n' => "\n", '\\t' => "\t", '\\r' => "\r" ] );
	}

	if ( $is_key ) {
		return $value;
	}

	// Lists, inline collections, anchors, aliases, tags and the like
	if ( preg_match( '/^(?:[\[\]{}&*!|>%@`\'"]|- |-$|\? )/', $value ) ) {
		throw new Exception( "Unsupported syntax at line $number" );
	}

	$lower = strtolower( $value );

	if ( 'null' === $lower || '~' === $value ) {
		return null;
	}

	if ( 'true' === $lower ) {
		return true;
	}

	if ( 'false' === $lower ) {
		return false;
	}

	if ( preg_match( '/^[+-]?(?:0|[1-9][0-9]*)$/', $value ) && strlen( ltrim( $value, '+-' ) ) < 16 ) {
		return (int) $value;
	}

	if ( preg_match( '/^[+-]?(?:[0-9]+\.[0-9]*|\.[0-9]+)(?:[eE][+-]?[0-9]+)?$/', $value ) ) {
		return (float) $value;
	}

	return $value;
}
