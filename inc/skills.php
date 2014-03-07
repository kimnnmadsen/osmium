<?php
/* Osmium
 * Copyright (C) 2014 Josiah Boning <jboning@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Osmium\Skills;

/**
 * Takes in an array of item/module type IDs; fills the $result array with entries like:
 *     input_type_id => array(
 *         skill_type_id => required_level,
 *         ...
 *     )
 */
function get_skill_prerequisites_for_types(array $types, array &$result) {
	foreach ($types as $typeid) {
		if(!isset($result[$typeid])) {
			$result[$typeid] = [];
		}

		foreach(\Osmium\Fit\get_required_skills($typeid) as $stid => $slevel) {
			if(!isset($result[$stid])) {
				get_skill_prerequisites_for_types([ $stid ], $result);
			}

			$result[$typeid][$stid] = $slevel;
		}
	}
}

/**
 * @param $prereqs Prerequisite arrays by type.
 */
function get_missing_prerequisites(array $prereqs, array $skillset, array &$result) {
	foreach($prereqs as $tid => $type_prereqs) {
		foreach($type_prereqs as $stid => $level) {
			$current = isset($skillset['override'][$stid])
				? $skillset['override'][$stid] : $skillset['default'];

			if($current < $level) {
				if (!isset($result[$tid])) {
					$result[$tid] = array();
				}
				$result[$tid][$stid] = $level;
			}
		}
	}
}

function merge_skill_prerequisites($requisites) {
	$s = [];
	foreach($requisites as $tid => $sub) {
		foreach($sub as $stid => $sl) {
			if(!isset($s[$stid])) $s[$stid] = $sl;
			else $s[$stid] = max($sl, $s[$stid]);
		}
	}
	return $s;
}

function sp_to_level_at_rank($level, $rank) {
	if ($level == 0) {
		return 0;
	}
	return ceil(250.0 * $rank * pow(2, 2.5 * ($level - 1.0)));
}

/**
 * @return [ $missingsp, $totalsp ]
 */
function sp_totals($prereqs_unique, $skillset) {
	$totalsp = 0;
	$missingsp = 0;
	foreach($prereqs_unique as $stid => $level) {
		$current = isset($skillset['override'][$stid])
			? $skillset['override'][$stid] : $skillset['default'];

		$rank = \Osmium\Fit\get_skill_rank($stid);
		$needed = sp_to_level_at_rank($level, $rank);

		$totalsp += $needed;

		if($current >= $level) {
			continue;
		}

		$missingsp += $needed - sp_to_level_at_rank($current, $rank);
	}
	return [ $missingsp, $totalsp ];
}
