<?php
/**
 * Class JsonBehavior
 *
 * @author Michal Turek <asgraf@gmail.com>
 * @link https://github.com/Asgraf/AsgrafJsonField
 */
class JsonBehavior extends ModelBehavior {
	private $fields = array();

	public function setup(Model $Model, $fields = array()) {
		if(!empty($fields)) {
			$this->fields[$Model->alias] = (array)$fields;
		}
	}

	function beforeSave(Model $Model,$options=array()) {
		$jsonfields = Hash::extract($this->fields,$Model->alias);
		if(!empty($jsonfields)) {
			if($Model->id) {
				$id = $Model->id;
				$data = $Model->data;
				$row = $Model->read($jsonfields,$id);
				$Model->id = $id;
				$Model->data = $data;
			}
			foreach($jsonfields as $fieldname) {
				if(array_key_exists($fieldname,$Model->data[$Model->alias])) {
					if(is_array($Model->data[$Model->alias][$fieldname])) {
						if($Model->id) {
							$oldfield_array = $row[$Model->alias][$fieldname]?:array();
							$new_val = array_filter(Hash::merge($oldfield_array,$Model->data[$Model->alias][$fieldname]));
						} else {
							$new_val = array_filter($Model->data[$Model->alias][$fieldname]);
						}
						$Model->data[$Model->alias][$fieldname] = empty($new_val)?null:json_encode($new_val);
					} else {
						$Model->data[$Model->alias][$fieldname] = null;
					}
				}
			}
		}
		return true;
	}

	public function beforeFind(Model $Model, $query) {
		foreach(Hash::extract($this->fields,$Model->alias) as $fieldname) {
			if(!empty($query['conditions'][$fieldname]) && is_array($query['conditions'][$fieldname])) {
				$query['conditions'][$fieldname]=json_encode($query['conditions'][$fieldname]);
			}
			if(!empty($query['conditions'][$Model->alias.'.'.$fieldname]) && is_array($query['conditions'][$Model->alias.'.'.$fieldname])) {
				$query['conditions'][$fieldname]=json_encode($query['conditions'][$Model->alias.'.'.$fieldname]);
			}
		}
		return $query;
	}

	function afterFind(Model $Model,$results,$primary = false) {
		foreach ($results as &$result) {
			foreach(Hash::extract($this->fields,$Model->alias) as $fieldname) {
				if(!empty($result[$Model->alias][$fieldname])) {
					$result[$Model->alias][$fieldname] = json_decode($result[$Model->alias][$fieldname],true);
				}
			}
		}
		return $results;
	}
}