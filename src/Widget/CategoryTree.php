<?php

/*
 * Copyright (c) 2017 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0+
 */

namespace HeimrichHannot\CategoriesBundle\Widget;

use Contao\FormHidden;
use HeimrichHannot\CategoriesBundle\Backend\Category;
use HeimrichHannot\CategoriesBundle\Model\CategoryModel;
use HeimrichHannot\Haste\Util\StringUtil;

class CategoryTree extends \Widget
{
    /**
     * Submit user input.
     *
     * @var bool
     */
    protected $blnSubmitInput = true;

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'be_widget';

    /**
     * Load the database object.
     *
     * @param array $arrAttributes
     */
    public function __construct($arrAttributes = null)
    {
        $this->import('Database');
        parent::__construct($arrAttributes);
    }

    /**
     * Generate the widget and return it as string.
     *
     * @return string
     */
    public function generate()
    {
        $arrSet = [];
        $arrValues = [];

        $dca = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strName];

        $usePrimaryCategory = isset($dca['eval']['addPrimaryCategory']) && $dca['eval']['addPrimaryCategory'];
        $primaryCategory = '';

        if (!empty($this->varValue)) { // can be an array
            if ($usePrimaryCategory) {
                if ('reloadCategoryTree' === \Input::post('action')) {
                    $value = [];

                    foreach ($this->varValue as $category) {
                        if (StringUtil::startsWith($category, 'primary_')) {
                            $primaryCategory = str_replace('primary_', '', $category);
                        } else {
                            $value[] = $category;
                        }
                    }

                    $this->varValue = $value;
                } else {
                    $primaryCategory = $this->activeRecord->{$this->strName.Category::PRIMARY_CATEGORY_SUFFIX};
                }
            }

            $objCategories = CategoryModel::findMultipleByIds((array) $this->varValue);

            if (null !== $objCategories) {
                while ($objCategories->next()) {
                    $arrSet[] = $objCategories->id;
                    $arrValues[$objCategories->id] = \Image::getHtml('iconPLAIN.svg').' '.$objCategories->title;
                }
            }
        }

        $return = '<input type="hidden" name="'.$this->strName.'" id="ctrl_'.$this->strId.'" value="'.implode(',', $arrSet).'">
  <div class="selector_container">
    <ul id="sort_'.$this->strId.'">';

        foreach ($arrValues as $k => $v) {
            $return .= '<li'.($k === $primaryCategory && $usePrimaryCategory ? ' class="tl_green"' : '').' data-id="'.$k.'">'.$v.'</li>';
        }

        $return .= '</ul>';

        if ($usePrimaryCategory) {
            $primaryCategoryWidget = new FormHidden(\Widget::getAttributesFromDca($dca, $this->strName.Category::PRIMARY_CATEGORY_SUFFIX, $primaryCategory));

            $return = $primaryCategoryWidget->parse().$return;
        }

        if (!\System::getContainer()->get('contao.picker.builder')->supportsContext('category')) {
            $return .= '
	<p><button class="tl_submit" disabled>'.$GLOBALS['TL_LANG']['MSC']['changeSelection'].'</button></p>';
        } else {
            $extras = [
                'fieldType' => $this->fieldType,
                'source' => $this->strTable.'.'.$this->currentRecord,
            ];

            if (is_array($this->rootNodes)) {
                $extras['rootNodes'] = array_values($this->rootNodes);
            }

            $return .= '
	<p><a href="'.ampersand(\System::getContainer()->get('contao.picker.builder')->getUrl('category', $extras)).'" class="tl_submit" id="pt_'.$this->strName.'">'.$GLOBALS['TL_LANG']['MSC']['changeSelection'].'</a></p>
	<script>
	  $("pt_'.$this->strName.'").addEvent("click", function(e) {
		e.preventDefault();
		Backend.openModalSelector({
		  "id": "tl_listing",
		  "title": "'.\StringUtil::specialchars(str_replace("'", "\\'", $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label'][0])).'",
		  "url": this.href + document.getElementById("ctrl_'.$this->strId.'").value + "&category_field='.$this->strField.'&category_table='.$this->strTable.($usePrimaryCategory ? '&primaryCategory='.$primaryCategory : '').($usePrimaryCategory ? '&usePrimaryCategory=1' : '').'",
		  "callback": function(table, value) {
			new Request.Contao({
			  evalScripts: false,
			  onSuccess: function(txt, json) {
				$("ctrl_'.$this->strId.'").getParent("div").set("html", json.content);
				json.javascript && Browser.exec(json.javascript);
			  }
			}).post({"action":"reloadCategoryTree", "name":"'.$this->strId.'", "value":value.join("\t"), "REQUEST_TOKEN":"'.REQUEST_TOKEN.'"});
		  }
		});
	  });
	</script>';
        }

        $return = '<div>'.$return.'</div></div>';

        return $return;
    }

    /**
     * Return an array if the "multiple" attribute is set.
     *
     * @param mixed $varInput
     *
     * @return mixed
     */
    protected function validator($varInput)
    {
        $this->checkValue($varInput);

        if ($this->hasErrors()) {
            return '';
        }

        // Return the value as usual
        if ('' === $varInput) {
            if ($this->mandatory) {
                $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
            }

            return '';
        } elseif (false === strpos($varInput, ',')) {
            return $this->multiple ? [(int) $varInput] : (int) $varInput;
        }
        $arrValue = array_map('intval', array_filter(explode(',', $varInput)));

        return $this->multiple ? $arrValue : $arrValue[0];
    }

    /**
     * Check the selected value.
     *
     * @param mixed $varInput
     */
    protected function checkValue($varInput)
    {
        if ('' === $varInput || !is_array($this->rootNodes)) {
            return;
        }

        if (false === strpos($varInput, ',')) {
            $arrIds = [(int) $varInput];
        } else {
            $arrIds = array_map('intval', array_filter(explode(',', $varInput)));
        }

//        if (count(array_diff($arrIds, array_merge($this->rootNodes, $this->Database->getChildRecords($this->rootNodes, 'tl_page')))) > 0) {
//            $this->addError($GLOBALS['TL_LANG']['ERR']['invalidPages']);
//        }
    }
}
