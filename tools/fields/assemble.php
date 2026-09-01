<?php
/**
 * Assembles the page_sections layouts outside WordPress.
 *
 * The field layer only touches a handful of WP/ACF functions, so stubbing those is enough
 * to run the real Page_Content + Common_Fields + Grid_Display code and see exactly what an
 * editor would get - tabs, order, per-layout Grid & Display membership, key prefixing.
 *
 * Not a substitute for the plugin running in WordPress, but it exercises the assembly
 * logic, which is where the bugs live, and it works when the site does not.
 *
 *   php tools/fields/assemble.php
 */

define('ABSPATH', __DIR__);
define('ACBS_PATH', dirname(__DIR__, 2).'/');

// --- WP / ACF stubs -------------------------------------------------------------------
function __($s, $d = null) { return $s; }
function esc_html($s) { return $s; }
function apply_filters($tag, $value, ...$args) { return $value; }
function add_filter(...$a) { return true; }
function add_action(...$a) { return true; }
function get_option($n, $d = false) { return $d; }
function get_posts($a = []) { return []; }
function get_post_types($a = []) { return ['post' => 'post', 'page' => 'page']; }
function get_taxonomies($a = []) { return ['category' => 'category']; }
function acf_get_fields($g) { return []; }
function acf_add_local_field_group($g) { return true; }
function wp_list_pluck($l, $f) { return array_column($l, $f); }

/** Close enough to ACF's own: it fills defaults and derives _name, which is what matters. */
function acf_validate_field($field = []) {
	$field = array_merge([
		'ID' => 0, 'key' => '', 'label' => '', 'name' => '', 'type' => 'text',
		'value' => null, 'menu_order' => 0, 'instructions' => '', 'required' => false,
		'id' => '', 'class' => '', 'conditional_logic' => false, 'parent' => 0, 'wrapper' => [],
	], $field);
	$field['_name'] = $field['name'];
	return $field;
}

/**
 * The location-rule classes extend ACF's own base class, and Contributor_Groups pulls
 * them in while looking for site-contributed fields. A bare stub is enough: nothing here
 * calls into it, and no groups exist to find.
 */
class ACF_Location {
	public $name = '';
	public $label = '';
	public $category = '';
	public $object_type = '';
	public function initialize() {}
	public function get_value($v = null) { return $v; }
	public function get_values($rule) { return []; }
	public function get_operators($rule) { return []; }
	public function match($rule, $screen, $field_group) { return false; }
}
function acf_register_location_type($c) { return true; }

// --- The real code --------------------------------------------------------------------
spl_autoload_register(function($class) {
	if(0 !== strpos($class, 'ACBS\\')) { return; }
	$file = strtolower(preg_replace(
		['/^ACBS\\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\\/'],
		['', '$1-$2', '-', DIRECTORY_SEPARATOR],
		$class
	));
	$path = ACBS_PATH.$file.'.php';
	if(is_readable($path)) { require $path; }
});

use ACBS\Modules\FlexibleLayoutTemplate\Fields\Page_Content;
use ACBS\Modules\FlexibleLayoutTemplate\Fields\Common_Fields;

$field = ['layouts' => Page_Content::get_base_layouts()];
$field = Common_Fields::inject_common_fields($field);

$total = 0;

foreach($field['layouts'] as $layout) {

	$tab = null;
	$byTab = [];

	foreach($layout['sub_fields'] as $sub) {
		if('tab' === $sub['type']) { $tab = $sub['label']; $byTab[$tab] = []; continue; }
		if(null === $tab) { $tab = '(untabbed)'; $byTab[$tab] = []; }
		$byTab[$tab][] = $sub['name'];
		$total++;
	}

	printf("\n%s  [%s]\n", $layout['name'], $layout['label']);
	foreach($byTab as $t => $names) {
		printf("   %-16s %s\n", $t, implode(', ', $names));
	}

}

printf("\n%d layouts, %d fields total\n", count($field['layouts']), $total);

// --- Overrides ------------------------------------------------------------------------
echo "\n=== per-layout overrides ===\n";
foreach($field['layouts'] as $layout) {
	foreach($layout['sub_fields'] as $sub) {
		if(in_array($sub['name'], ['layout_columns', 'layout_columns_alignment'], true)) {
			printf("%-26s %-26s label=%-32s default=%-8s choices=%d\n",
				$layout['name'], $sub['name'], "'".$sub['label']."'",
				var_export($sub['default_value'] ?? null, true), count($sub['choices'] ?? []));
		}
	}
}

// --- Key collisions -------------------------------------------------------------------
$seen = [];
$dupes = [];
foreach($field['layouts'] as $layout) {
	foreach($layout['sub_fields'] as $sub) {
		if('' === $sub['key']) { continue; }
		if(isset($seen[$sub['key']])) { $dupes[] = $sub['key']." (".$seen[$sub['key']]." + ".$layout['name'].")"; }
		$seen[$sub['key']] = $layout['name'];
	}
}
echo "\n=== duplicate field keys across layouts: ".(count($dupes) ? count($dupes) : 'none')." ===\n";
$grid_dupes = array_values(array_filter($dupes, function($d) { return false !== strpos($d, 'field_grid_'); }));
echo "   of which Grid & Display: ".count($grid_dupes)."\n";
echo "   the rest are Other Settings + the Content tab, which are deliberately NOT\n";
echo "   key-prefixed per layout - see the Common_Fields class comment.\n";

// --- Conditional logic ----------------------------------------------------------------
echo "\n=== card conditional logic, resolved ===\n";
foreach($field['layouts'] as $layout) {
	$keys = array_column($layout['sub_fields'], 'name', 'key');
	foreach($layout['sub_fields'] as $sub) {
		if(!in_array($sub['name'], ['layout_display_bg', 'layout_display_bg_colour'], true)) { continue; }
		$parts = [];
		foreach((array) ($sub['conditional_logic'] ?: []) as $group) {
			foreach($group as $c) {
				$target = $keys[$c['field']] ?? 'MISSING('.$c['field'].')';
				$parts[] = $target.' == '.var_export($c['value'] ?? null, true);
			}
		}
		printf("%-20s %-26s %s\n", $layout['name'], $sub['name'], implode(' AND ', $parts));
	}
}
