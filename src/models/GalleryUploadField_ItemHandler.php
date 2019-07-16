<?php

use SilverStripe\Forms\Form;
use SilverStripe\AssetAdmin\Forms\UploadField;

class GalleryUploadField_ItemHandler extends UploadField
{

    protected $pageID;

    /**
     * @param UploadFIeld $parent
     * @param int $item
     * @param int $pageID
     */
    public function __construct($parent, $itemID, $pageID)
    {
        $this->parent = $parent;
        $this->itemID = $itemID;
        $this->pageID = $pageID;

        parent::__construct($parent, $itemID);
    }

    private static $allowed_actions = array(
        'EditForm',
        'doEdit',
        'admin'
    );

    public function EditForm()
    {

        $file = $this->getItem();

        if ($file->hasMethod('getUploadFields')) {
            $fields = $file->getUploadFields();
        } elseif (is_a($this->parent->getConfig('fileEditFields'), 'FieldList')) {
            $fields = $this->parent->getConfig('fileEditFields');
        } elseif ($file->hasMethod($this->parent->getConfig('fileEditFields'))) {
            $fields = $file->{$this->parent->getConfig('fileEditFields')}();
        } else {
            $fields = $file->getCMSFields();
            // Only display main tab, to avoid overly complex interface
            if ($fields->hasTabSet() && $mainTab = $fields->findOrMakeTab('Root.Main')) $fields = $mainTab->Fields();
        }
        if (is_a($this->parent->getConfig('fileEditActions'), 'FieldList')) {
            $actions = $this->parent->getConfig('fileEditActions');
        } elseif ($file->hasMethod($this->parent->getConfig('fileEditActions'))) {
            $actions = $file->{$this->parent->getConfig('fileEditActions')}();
        } else {
            $actions = new FieldList($saveAction = new FormAction('doEdit', _t('UploadField.DOEDIT', 'Save')));
            $saveAction->addExtraClass('ss-ui-action-constructive icon-accept');
        }
        if (is_a($this->parent->getConfig('fileEditValidator'), 'Validator')) {
            $validator = $this->parent->getConfig('fileEditValidator');
        } elseif ($file->hasMethod($this->parent->getConfig('fileEditValidator'))) {
            $validator = $file->{$this->parent->getConfig('fileEditValidator')}();
        } else {
            $validator = null;
        }
        $form = new Form(
            $this,
            __FUNCTION__,
            $fields,
            $actions,
            $validator
        );
        $form->loadDataFrom($file);

        //Get join object for populating caption
        $parentID = $this->pageID;
        $record = Page::get()
            ->where("\"SiteTree\".\"ID\" = '$parentID'")
            ->first();

        list($parentClass, $componentClass, $parentField, $componentField, $table) = $record->many_many('Images');

        $joinObj = $table::get()
            ->where("\"$parentField\" = '{$parentID}' AND \"ImageID\" = '{$file->ID}'")
            ->first();

        $data = array(
            'Caption' => $joinObj->Caption
        );
        $form->loadDataFrom($data);

        $form->addExtraClass('small');
        return $form;
    }

    public function doEdit(array $data, Form $form, SS_HTTPRequest $request)
    {

        // Check form field state
        if ($this->parent->isDisabled() || $this->parent->isReadonly()) return $this->httpError(403);

        // Check item permissions
        $item = $this->getItem();
        if (!$item) return $this->httpError(404);
        if (!$item->canEdit()) return $this->httpError(403);

        // Only allow actions on files in the managed relation (if one exists)
        $items = $this->parent->getItems();
        if ($this->parent->managesRelation() && !$items->byID($item->ID)) return $this->httpError(403);

        //Get join to save the caption onto it
        $record = $this->parent->getRecord();
        $relName = $this->parent->getName();
        $parentID = $record->ID;
        list($parentClass, $componentClass, $parentField, $componentField, $table) = $record->many_many($relName);

        $joinObj = $table::get()
            ->where("\"$parentField\" = '{$parentID}' AND \"$componentField\" = '{$item->ID}'")
            ->first();

        $form->saveInto($joinObj);
        $joinObj->write();

        $form->saveInto($item);
        $item->write();

        $form->sessionMessage(_t('UploadField.Saved', 'Saved'), 'good');

        return $this->edit($request);
    }
}
