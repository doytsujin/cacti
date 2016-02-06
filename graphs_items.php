<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2016 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

include('./include/auth.php');
include_once('./lib/utility.php');

/* set default action */
set_default_action();

switch ($_REQUEST['action']) {
	case 'save':
		form_save();

		break;
	case 'item_remove':
		item_remove();

		header('Location: graphs.php?header=false&action=graph_edit&id=' . $_REQUEST['local_graph_id']);
		break;
	case 'item_edit':
		top_header();

		item_edit();

		bottom_footer();
		break;
	case 'item_movedown':
		item_movedown();

		header('Location: graphs.php?header=false&action=graph_edit&id=' . $_REQUEST['local_graph_id']);
		break;
	case 'item_moveup':
		item_moveup();

		header('Location: graphs.php?header=false&action=graph_edit&id=' . $_REQUEST['local_graph_id']);
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if (isset($_POST['save_component_item'])) {
		global $graph_item_types;

		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post('sequence'));
		input_validate_input_number(get_request_var_post('graph_type_id'));
		input_validate_input_number(get_request_var_post('local_graph_id'));
		input_validate_input_number(get_request_var_post('graph_template_item_id'));
		input_validate_input_number(get_request_var_post('graph_template_id'));
		input_validate_input_number(get_request_var_post('local_graph_template_item_id'));
		/* ==================================================== */

		$items[0] = array();

		if ($graph_item_types{$_POST['graph_type_id']} == 'LEGEND') {
			/* this can be a major time saver when creating lots of graphs with the typical
			GPRINT LAST/AVERAGE/MAX legends */
			$items = array(
				0 => array(
					'color_id' => '0',
					'graph_type_id' => '9',
					'consolidation_function_id' => '4',
					'text_format' => 'Current:',
					'hard_return' => ''
					),
				1 => array(
					'color_id' => '0',
					'graph_type_id' => '9',
					'consolidation_function_id' => '1',
					'text_format' => 'Average:',
					'hard_return' => ''
					),
				2 => array(
					'color_id' => '0',
					'graph_type_id' => '9',
					'consolidation_function_id' => '3',
					'text_format' => 'Maximum:',
					'hard_return' => 'on'
					));
		}

		foreach ($items as $item) {
			/* generate a new sequence if needed */
			if (empty($_POST['sequence'])) {
				$_POST['sequence'] = get_sequence($_POST['sequence'], 'sequence', 'graph_templates_item', 'local_graph_id=' . $_POST['local_graph_id']);
			}
			$save['id'] = $_POST['graph_template_item_id'];
			$save['graph_template_id'] = $_POST['graph_template_id'];
			$save['local_graph_template_item_id'] = $_POST['local_graph_template_item_id'];
			$save['local_graph_id'] = $_POST['local_graph_id'];
			$save['task_item_id'] = form_input_validate($_POST['task_item_id'], 'task_item_id', '^[0-9]+$', true, 3);
			$save['color_id'] = form_input_validate((isset($item['color_id']) ? $item['color_id'] : $_POST['color_id']), 'color_id', '^[0-9]+$', true, 3);
			/* if alpha is disabled, use invisible_alpha instead */
			if (!isset($_POST['alpha'])) {$_POST['alpha'] = $_POST['invisible_alpha'];}
			$save['alpha'] = form_input_validate((isset($item['alpha']) ? $item['alpha'] : $_POST['alpha']), 'alpha', '', true, 3);
			$save['graph_type_id'] = form_input_validate((isset($item['graph_type_id']) ? $item['graph_type_id'] : $_POST['graph_type_id']), 'graph_type_id', '^[0-9]+$', true, 3);
			$save['cdef_id'] = form_input_validate($_POST['cdef_id'], 'cdef_id', '^[0-9]+$', true, 3);
			$save['consolidation_function_id'] = form_input_validate((isset($item['consolidation_function_id']) ? $item['consolidation_function_id'] : $_POST['consolidation_function_id']), 'consolidation_function_id', '^[0-9]+$', true, 3);
			$save['text_format'] = form_input_validate((isset($item['text_format']) ? $item['text_format'] : $_POST['text_format']), 'text_format', '', true, 3);
			$save['value'] = form_input_validate($_POST['value'], 'value', '', true, 3);
			$save['hard_return'] = form_input_validate(((isset($item['hard_return']) ? $item['hard_return'] : (isset($_POST['hard_return']) ? $_POST['hard_return'] : ''))), 'hard_return', '', true, 3);
			$save['gprint_id'] = form_input_validate($_POST['gprint_id'], 'gprint_id', '^[0-9]+$', true, 3);
			$save['sequence'] = $_POST['sequence'];

			if (!is_error_message()) {
				$graph_template_item_id = sql_save($save, 'graph_templates_item');

				if ($graph_template_item_id) {
					raise_message(1);
				}else{
					raise_message(2);
				}
			}

			$_POST['sequence'] = 0;
		}

		if (is_error_message()) {
			header('Location: graphs.php?header=false&action=item_edit&graph_template_item_id=' . (empty($graph_template_item_id) ? $_POST['graph_template_item_id'] : $graph_template_item_id) . '&id=' . $_POST['local_graph_id']);
			exit;
		}else{
			header('Location: graphs.php?header=false&action=graph_edit&id=' . $_POST['local_graph_id']);
			exit;
		}
	}
}

/* -----------------------
    item - Graph Items
   ----------------------- */

function item_movedown() {
	global $graph_item_types;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	input_validate_input_number(get_request_var('local_graph_id'));
	/* ==================================================== */

	$arr = get_graph_group($_REQUEST['id']);
	$next_id = get_graph_parent($_REQUEST['id'], 'next');

	if ((!empty($next_id)) && (isset($arr{$_REQUEST['id']}))) {
		move_graph_group($_REQUEST['id'], $arr, $next_id, 'next');
	}elseif (preg_match('/(GPRINT|VRULE|HRULE|COMMENT)/', $graph_item_types{db_fetch_cell_prepared('SELECT graph_type_id FROM graph_templates_item WHERE id = ?', array(get_request_var('id')))})) {
		move_item_down('graph_templates_item', $_REQUEST['id'], 'local_graph_id=' . $_REQUEST['local_graph_id']);
	}
}

function item_moveup() {
	global $graph_item_types;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	input_validate_input_number(get_request_var('local_graph_id'));
	/* ==================================================== */

	$arr = get_graph_group($_REQUEST['id']);
	$previous_id = get_graph_parent($_REQUEST['id'], 'previous');

	if ((!empty($previous_id)) && (isset($arr{$_REQUEST['id']}))) {
		move_graph_group($_REQUEST['id'], $arr, $previous_id, 'previous');
	}elseif (preg_match('/(GPRINT|VRULE|HRULE|COMMENT)/', $graph_item_types{db_fetch_cell_prepared('SELECT graph_type_id FROM graph_templates_item WHERE id = ?', array(get_request_var('id')))})) {
		move_item_up('graph_templates_item', $_REQUEST['id'], 'local_graph_id=' . $_REQUEST['local_graph_id']);
	}
}

function item_remove() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	/* ==================================================== */

	db_execute_prepared('DELETE FROM graph_templates_item WHERE id = ?', array(get_request_var('id')));
}

function item_edit() {
	global $struct_graph_item, $graph_item_types, $consolidation_functions;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('id'));
	input_validate_input_number(get_request_var('host_id'));
	input_validate_input_number(get_request_var('graph_template_id'));
	input_validate_input_number(get_request_var('local_graph_id'));
	input_validate_input_number(get_request_var('host_id'));
	input_validate_input_number(get_request_var('data_template_id'));
	/* ==================================================== */

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value('local_graph_id', 'sess_local_graph_id', '');
	load_current_session_value('host_id', 'sess_ds_host_id', '-1');
	load_current_session_value('data_template_id', 'sess_data_template_id', '-1');

	$id = (!empty($_REQUEST['id']) ? '&id=' . $_REQUEST['id'] : '');
	$host = db_fetch_row_prepared('SELECT hostname FROM host WHERE id = ?', array(get_request_var('host_id')));

	html_start_box('Data Sources [host: ' . (empty($host['hostname']) ? 'No Device' : $host['hostname']) . ']', '100%', '', '3', 'center', '');

	?>
	<tr class='even noprint'>
		<form name='form_graph_items' action='graphs_items.php'>
		<td>
			<table>
				<tr>
					<td width='50'>
						Device
					</td>
					<td>
						<select name='cbo_host_id' onChange='window.location=document.form_graph_items.cbo_host_id.options[document.form_graph_items.cbo_host_id.selectedIndex].value'>
							<option value='graphs_items.php?action=item_edit<?php print $id; ?>&local_graph_id=<?php print get_request_var('local_graph_id');?>&host_id=-1&data_template_id=<?php print get_request_var('data_template_id');?>'<?php if (get_request_var('host_id') == '-1') {?> selected<?php }?>>Any</option>
							<option value='graphs_items.php?action=item_edit<?php print $id; ?>&local_graph_id=<?php print get_request_var('local_graph_id');?>&host_id=0&data_template_id=<?php print get_request_var('data_template_id');?>'<?php if (get_request_var('host_id') == '0') {?> selected<?php }?>>None</option>
							<?php
							$hosts = db_fetch_assoc("SELECT id, CONCAT_WS('',description,' (',hostname,')') AS name FROM host ORDER BY description, hostname");

							if (sizeof($hosts) > 0) {
								foreach ($hosts as $host) {
									print "<option value='graphs_items.php?action=item_edit" . $id . '&local_graph_id=' . get_request_var('local_graph_id') . '&host_id=' . $host['id'] . '&data_template_id=' . get_request_var('data_template_id') . "'"; if (get_request_var('host_id') == $host['id']) { print ' selected'; } print '>' . $host['name'] . "</option>\n";
								}
							}
							?>

						</select>
					</td>
				</tr>
				<tr>
					<td class='nowrap'>
						Data Template
					</td>
					<td>
						<select name='cbo_data_template_id' onChange='window.location=document.form_graph_items.cbo_data_template_id.options[document.form_graph_items.cbo_data_template_id.selectedIndex].value'>
							<option value='graphs_items.php?action=item_edit<?php print $id; ?>&local_graph_id=<?php print get_request_var('local_graph_id');?>&data_template_id=-1&host_id=<?php print get_request_var('host_id');?>'<?php if (get_request_var('data_template_id') == '-1') {?> selected<?php }?>>Any</option>
							<option value='graphs_items.php?action=item_edit<?php print $id; ?>&local_graph_id=<?php print get_request_var('local_graph_id');?>&data_template_id=0&host_id=<?php print get_request_var('host_id');?>'<?php if (get_request_var('data_template_id') == '0') {?> selected<?php }?>>None</option>
							<?php
							$data_templates = db_fetch_assoc('SELECT id, name FROM data_template ORDER BY name');

							if (sizeof($data_templates) > 0) {
								foreach ($data_templates as $data_template) {
									print "<option value='graphs_items.php?action=item_edit" . $id . '&local_graph_id=' . get_request_var('local_graph_id') . '&data_template_id=' . $data_template['id']. '&host_id=' . get_request_var('host_id') . "'"; if (get_request_var('data_template_id') == $data_template['id']) { print ' selected'; } print '>' . $data_template['name'] . "</option>\n";
								}
							}
							?>

						</select>
					</td>
				</tr>
			</table>
		</td>
		</form>
	</tr>
	<?php

	html_end_box();

	if (get_request_var('host_id') == '-1') {
		$sql_where = '';
	}elseif (get_request_var('host_id') == '0') {
		$sql_where = ' data_local.host_id=0 AND ';
	}elseif (!empty($_REQUEST['host_id'])) {
		$sql_where = ' data_local.host_id=' . get_request_var('host_id') . ' AND ';
	}

	if (get_request_var('data_template_id') == '-1') {
		$sql_where .= '';
	}elseif (get_request_var('data_template_id') == '0') {
		$sql_where .= ' data_local.data_template_id=0 AND ';
	}elseif (!empty($_REQUEST['data_template_id'])) {
		$sql_where .= ' data_local.data_template_id=' . get_request_var('data_template_id') . ' AND ';
	}

	if (!empty($_REQUEST['id'])) {
		$template_item = db_fetch_row_prepared('SELECT * FROM graph_templates_item WHERE id = ?', array(get_request_var('id')));
		$host_id = db_fetch_cell_prepared('SELECT host_id FROM graph_local WHERE id = ?', array(get_request_var('local_graph_id')));
	}

	$header_label = '[edit graph: ' . db_fetch_cell_prepared('SELECT title_cache FROM graph_templates_graph WHERE local_graph_id = ?'. array(get_request_var('local_graph_id'))) . ']';

	html_start_box("Graph Items $header_label", '100%', '', '3', 'center', '');

	/* by default, select the LAST DS chosen to make everyone's lives easier */
	if (!empty($_REQUEST['local_graph_id'])) {
		$default = db_fetch_row_prepared('SELECT task_item_id FROM graph_templates_item WHERE local_graph_id = ? ORDER BY sequence DESC', array(get_request_var('local_graph_id')));

		if (sizeof($default) > 0) {
			$struct_graph_item['task_item_id']['default'] = $default['task_item_id'];
		}else{
			$struct_graph_item['task_item_id']['default'] = 0;
		}

		/* modifications to the default graph items array */
		$struct_graph_item['task_item_id']['sql'] = "select
			CONCAT_WS('',data_template_data.name_cache,' (',data_template_rrd.data_source_name,')') as name,
			data_template_rrd.id
			from (data_template_data,data_template_rrd,data_local)
			left join host on (data_local.host_id=host.id)
			where data_template_rrd.local_data_id=data_local.id
			and data_template_data.local_data_id=data_local.id ";
		/* Make sure we don't limit the list so that the selected DS isn't in the list in edit mode */
		if (strlen($sql_where) > 0) {
			$sql_where = substr($sql_where,0,-5);
			if (!empty($_REQUEST['id'])) {
				$struct_graph_item['task_item_id']['sql'] .= ' AND ((' . $sql_where .  ') OR (data_template_rrd.id = ' .  $template_item['task_item_id'] . '))';
			} else {
				$struct_graph_item['task_item_id']['sql'] .= ' and (' . $sql_where . ')';
			}
		}
		$struct_graph_item['task_item_id']['sql'] .= ' ORDER BY name';
	}

	$form_array = array();

	while (list($field_name, $field_array) = each($struct_graph_item)) {
		$form_array += array($field_name => $struct_graph_item[$field_name]);

		$form_array[$field_name]['value'] = (isset($template_item) ? $template_item[$field_name] : '');
		$form_array[$field_name]['form_id'] = (isset($template_item) ? $template_item['id'] : '0');
	}

	draw_edit_form(
		array(
			'config' => array(
				),
			'fields' => $form_array
			)
		);

	form_hidden_box('local_graph_id', get_request_var('local_graph_id'), '0');
	form_hidden_box('graph_template_item_id', (isset($template_item) ? $template_item['id'] : '0'), '');
	form_hidden_box('local_graph_template_item_id', (isset($template_item) ? $template_item['local_graph_template_item_id'] : '0'), '');
	form_hidden_box('graph_template_id', (isset($template_item) ? $template_item['graph_template_id'] : '0'), '');
	form_hidden_box('sequence', (isset($template_item) ? $template_item['sequence'] : '0'), '');
	form_hidden_box('_graph_type_id', (isset($template_item) ? $template_item['graph_type_id'] : '0'), '');
	form_hidden_box('save_component_item', '1', '');
	form_hidden_box('invisible_alpha', $form_array['alpha']['value'], 'FF');
	form_hidden_box('rrdtool_version', read_config_option('rrdtool_version'), '');

	html_end_box();

	form_save_button('graphs.php?action=graph_edit&id=' . get_request_var('local_graph_id'));
	?>
	<script type='text/javascript'>

	$(function() {
		setRowVisibility();
		$('#graph_type_id').change(function(data) {
			setRowVisibility();
		});
	});

	/* 
	columns - task_item_id color_id alpha graph_type_id consolidation_function_id cdef_id value gprint_id text_format hard_return

	graph_type_ids - 1 - Comment 2 - HRule 3 - Vrule 4 - Line1 5 - Line2 6 - Line3 7 - Area 8 - Stack 9 - Gprint 10 - Legend
	*/

	function setRowVisibility() {
		switch($('#graph_type_id').val()) {
		case '1':
			$('#row_task_item_id').hide();
			$('#row_color_id').hide();
			$('#row_alpha').hide();
			$('#row_consolidation_function_id').hide();
			$('#row_cdef_id').hide();
			$('#row_value').hide();
			$('#row_gprint_id').hide();
			$('#row_text_format').show();
			$('#row_hard_return').show();
			break;
		case '2':
		case '3':
			$('#row_task_item_id').hide();
			$('#row_color_id').hide();
			$('#row_alpha').hide();
			$('#row_consolidation_function_id').hide();
			$('#row_cdef_id').hide();
			$('#row_value').show();
			$('#row_gprint_id').hide();
			$('#row_text_format').hide();
			$('#row_hard_return').show();
			break;
		case '4':
		case '5':
		case '6':
		case '7':
		case '8':
			$('#row_task_item_id').show();
			$('#row_color_id').show();
			$('#row_alpha').show();
			$('#row_consolidation_function_id').show();
			$('#row_cdef_id').show();
			$('#row_value').hide();
			$('#row_gprint_id').show();
			$('#row_text_format').show();
			$('#row_hard_return').show();

			break;
		case '9':
		case '10':
			$('#row_task_item_id').show();
			$('#row_color_id').hide();
			$('#row_alpha').hide();
			$('#row_consolidation_function_id').hide();
			$('#row_cdef_id').show();
			$('#row_value').hide();
			$('#row_gprint_id').show();
			$('#row_text_format').hide();
			if ($('#graph_type_id').val() == 9) {
				$('#row_hard_return').show();
			}else{
				$('#row_hard_return').hide();
			}

			break;
		}

		$('#alpha').prop('disabled', true);
		if ($('#color_id').val() != 0) {
			$('#alpha').prop('disabled', false);
		}
	}

	</script>
	<?php
}

