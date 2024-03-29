<?php

/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_checkbox
{
    function &buildWidget(&$record, &$field, &$form, $formFieldName, $new = false)
    {
        $table = &$record->_table;
        $widget = &$field['widget'];

        if (!@$widget['separator']) $widget['separator'] = '<br />';
        $factory = &Dataface_FormTool::factory();
        if ((isset($field['repeat']) and $field['repeat'] and isset($field['vocabulary']) and $field['vocabulary']) or
            (isset($field['transient']) and isset($field['relationship']))
        ) {
            $boxes = array();
            $options = array();
            if (@$field['vocabulary']) {
                $options = &Dataface_FormTool::getVocabulary($record, $field);
                $options__classes = Dataface_FormTool::getVocabularyClasses($record, $field);
            } else if (isset($field['relationship'])) {
                $relationship = &$record->_table->getRelationship($field['relationship']);
                $options = $relationship->getAddableValues($record);
                $options__classes = array();

                // Now let's add the ability to add an option that isn't already there
                // but only if the user has permission
                if (!@$widget['suffix']) $widget['suffix'] = '';
                $dtable = &Dataface_Table::loadTable($relationship->getDomainTable());
                if (!PEAR::isError($dtable) and $record->checkPermission('add new related record', array('relationship' => $relationship->getName()))) {
                    import(XFROOT . 'Dataface/JavascriptTool.php');
                    $jt = Dataface_JavascriptTool::getInstance();
                    $jt->import('xataface/widgets/checkbox.js');

                    $suffix =
                        '<a
                                        class="xf-checkbox-widget-other-link"
                                        href="#"
                                        onclick="return false"
                                        id="' . df_escape($field['name']) . '-other"
                                        data-relationship-name="' . df_escape($relationship->getName()) . '"
                                        data-table-name="' . df_escape($dtable->tablename) . '"
                                        data-field-name="' . df_escape($field['name']) . '"
                                        data-keys="' . df_escape(json_encode(array_keys($dtable->keys()))) . '"
                                    >Other..</a>';


                    $widget['suffix'] = $suffix;
                }
            }


            if ($record and $record->val($field['name'])) {
                $vals = $record->val($field['name']);
                if (is_array($vals)) {
                    foreach ($vals as $thisval) {
                        if (!isset($options[$thisval])) {
                            $options[$thisval] = $thisval;
                        }
                    }
                }
            }
            $dummyForm = new HTML_QuickForm();
            foreach ($options as $opt_val => $opt_text) {
                if ($opt_val === '') continue;
                $boxes[] = &$dummyForm->createElement('checkbox', $opt_val, null, $opt_text, array('class' => 'checkbox-of-' . $field['name'] . ' ' . @$options__classes[$opt_val]));

            }
            if (is_array($form->_submitValues)) {
                $submitVal = @$form->_submitValues[$field['name']];
                if (is_string($submitVal) and strlen($submitVal) > 0 and $submitVal[0] == '[') {
                    $decoded = json_decode($submitVal, true);
                    $newVal = array();
                    foreach ($decoded as $v) {
                        $newVal[$v] = '1';
                    }
                    $form->_submitValues[$field['name']] = $newVal;
                }
            }
            $el = &$factory->addGroup($boxes, $field['name'], $widget['label']);
            if ($form->xml) {
                $xmlBody = '<vocabulary>';
                foreach ($options as $opt_val => $opt_text) {
                    $xmlBody .= '<value key="' . xmlentities($opt_val) . '">' . xmlentities($opt_text) . '</value>';
                }
                $xmlBody .= '</vocabulary>';
                $el->updateAttributes(array('data-xf-xml-body' => $xmlBody));
            }
        } else {
            $el = &$factory->addElement('advcheckbox', $formFieldName, $widget['label']);
            if ($field['vocabulary']) {
                $yes = '';
                $no = '';
                if ($table->isYesNoValuelist($field['vocabulary'], $yes, $no)) {
                    $el->setValues(array($no, $yes));
                }
            }
        }
        return $el;
    }

    function &pushValue(&$record, &$field, &$form, &$element, &$metaValues)
    {
        $table = &$record->_table;
        $formTool = &Dataface_FormTool::getInstance();
        $formFieldName = $element->getName();

        $val = $element->getValue();
        if ($field['repeat']) {
            if (is_array($val)) {
                $out = array_keys($val);
            } else {
                $out = array();
            }
        } else {
            if (preg_match('/int/', @$field['Type'])) {
                $out = intval($val);
            } else {
                $out = $val;
            }
        }
        if (PEAR::isError($val)) {
            $val->addUserInfo(
                df_translate(
                    'scripts.Dataface.QuickForm.pushValue.ERROR_PUSHING_VALUE',
                    "Error pushing value for field '$field[name]' in QuickForm::pushWidget() on line " . __LINE__ . " of file " . __FILE__,
                    array('name' => $field['name'], 'file' => __FILE__, 'line' => __LINE__)
                )
            );
            return $val;
        }
        return $out;
    }

    function pullValue(&$record, &$field, &$form, &$element, $new = false)
    {

        /*
		 *
		 * Checkbox widgets store values as associative array $a where
		 * $a[$x] == 1 <=> element named $x is checked.
		 * Note:  See _buildWidget() for information about how the checkbox widget is
		 * created.  It is created differently for repeat fields than it is for individual
		 * fields.  For starters, individual fields are advcheckbox widgets, whereas
		 * repeat fields are just normal checkbox widgets.
		 *
		 */
        $formFieldName = $element->getName();
        $raw = $record->getValue($field['name']);
        if (is_string($raw) and strtolower(substr($field['Type'], 0, 3)) == 'set') {
            $raw = explode(',', $raw);
        }
        if ($field['repeat'] and is_array($raw)) {
            // If the field is a repeat field $raw will be an array of
            // values.
            $v = array();
            foreach ($raw as $key => $value) {
                $v[$value] = 1;
            }
            // In this case we set this checkbox to the array of values that are currently checked.
            $val = $v;
        } else {
            // If the field is not a repeat, then it is only one value
            $val = $record->getValueAsString($field['name']);
        }

        return $val;
    }
}
