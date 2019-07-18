<?php

namespace SkyWest\Gallery\Fields;

use SilverStripe\Assets\File;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\FormField;
use SilverStripe\View\Requirements;
use SilverStripe\ORM\ValidationException;
use SilverStripe\AssetAdmin\Forms\UploadField;

class GalleryUploadField extends UploadField
{

    private static $allowed_actions = array(
        'upload',
        'attach',
        'handleItem',
        'handleSelect',
        'fileexists',
        'sort'
    );

    protected $templateFileEdit = 'GalleryUploadField_FileEdit';

    protected $ufConfig = array(
        /**
         * @var boolean
         */
        'autoUpload' => true,
        /**
         * php validation of allowedMaxFileNumber only works when a db relation is available, set to null to allow
         * unlimited if record has a has_one and allowedMaxFileNumber is null, it will be set to 1
         * @var int
         */
        'allowedMaxFileNumber' => null,
        /**
         * @var int
         */
        'previewMaxWidth' => 80,
        /**
         * @var int
         */
        'previewMaxHeight' => 60,
        /**
         * javascript template used to display uploading files
         * @see javascript/UploadField_uploadtemplate.js
         * @var string
         */
        'uploadTemplateName' => 'ss-uploadfield-uploadtemplate',
        /**
         * javascript template used to display already uploaded files
         * @see javascript/UploadField_downloadtemplate.js
         * @var string
         */
        'downloadTemplateName' => 'ss-uploadfield-downloadtemplate',
        /**
         * FieldList $fields or string $name (of a method on File to provide a fields) for the EditForm
         * @example 'getCMSFields'
         * @var FieldList|string
         */
        'fileEditFields' => 'getUploadFields',
        /**
         * FieldList $actions or string $name (of a method on File to provide a actions) for the EditForm
         * @example 'getCMSActions'
         * @var FieldList|string
         */
        'fileEditActions' => null,
        /**
         * Validator (eg RequiredFields) or string $name (of a method on File to provide a Validator) for the EditForm
         * @example 'getCMSValidator'
         * @var string
         */
        'fileEditValidator' => null
    );

    public function Field($properties = array())
    {

        $record = $this->getRecord();
        $name = $this->getName();

        // if there is a has_one relation with that name on the record and
        // allowedMaxFileNumber has not been set, it's wanted to be 1
        if (
            $record
            && $record->exists()
            && $record->has_one($name)
            && !$this->getConfig('allowedMaxFileNumber')
        ) {
            $this->setConfig('allowedMaxFileNumber', 1);
        }

        Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js');
        Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
        Requirements::javascript(FRAMEWORK_DIR . '/javascript/i18n.js');
        Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/ssui.core.js');

        Requirements::combine_files('uploadfield.js', array(
            THIRDPARTY_DIR . '/javascript-templates/tmpl.js',
            THIRDPARTY_DIR . '/javascript-loadimage/load-image.js',
            THIRDPARTY_DIR . '/jquery-fileupload/jquery.iframe-transport.js',
            THIRDPARTY_DIR . '/jquery-fileupload/cors/jquery.xdr-transport.js',
            THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload.js',
            THIRDPARTY_DIR . '/jquery-fileupload/jquery.fileupload-ui.js',
            FRAMEWORK_DIR . '/javascript/UploadField_uploadtemplate.js',
            FRAMEWORK_DIR . '/javascript/UploadField_downloadtemplate.js',
            FRAMEWORK_DIR . '/javascript/UploadField.js',
        ));

        Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
        // Requirements::javascript('gallery/javascript/GalleryUploadField.js');

        Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css'); // TODO hmmm, remove it?
        Requirements::css(FRAMEWORK_DIR . '/css/UploadField.css');
        // Requirements::css('gallery/css/GalleryUploadField.css');

        $allowedMaxFileNumber = $this->getAllowedMaxFileNumber();
        $config = array(
            'url' => $this->Link('upload'),
            'urlSelectDialog' => $this->Link('select'),
            'urlAttach' => $this->Link('attach'),
            'urlSort' => $this->Link('sort'),
            'urlFileExists' => $this->link('fileexists'),
            'acceptFileTypes' => '.+$',
            // Fileupload treats maxNumberOfFiles as the max number of _additional_ items allowed
            'maxNumberOfFiles' => $allowedMaxFileNumber ? ($allowedMaxFileNumber - count($this->getItemIDs())) : null
        );
        if (count($this->getValidator()->getAllowedExtensions())) {
            $allowedExtensions = $this->getValidator()->getAllowedExtensions();
            $config['acceptFileTypes'] = '(\.|\/)(' . implode('|', $allowedExtensions) . ')$';
            $config['errorMessages']['acceptFileTypes'] = _t(
                'File.INVALIDEXTENSIONSHORT',
                'Extension is not allowed'
            );
        }
        if ($this->getValidator()->getAllowedMaxFileSize()) {
            $config['maxFileSize'] = $this->getValidator()->getAllowedMaxFileSize();
            $config['errorMessages']['maxFileSize'] = _t(
                'File.TOOLARGESHORT',
                'Filesize exceeds {size}',
                array('size' => File::format_size($config['maxFileSize']))
            );
        }
        if ($config['maxNumberOfFiles'] > 1) {
            $config['errorMessages']['maxNumberOfFiles'] = _t(
                'UploadField.MAXNUMBEROFFILESSHORT',
                'Can only upload {count} files',
                array('count' => $config['maxNumberOfFiles'])
            );
        }
        $configOverwrite = array();
        if (is_numeric($config['maxNumberOfFiles']) && $this->getItems()->count()) {
            $configOverwrite['maxNumberOfFiles'] = $config['maxNumberOfFiles'] - $this->getItems()->count();
        }

        $config = array_merge($config, $this->ufConfig, $configOverwrite);

        return $this->customise(array(
            'configString' => str_replace('"', "&quot;", Convert::raw2json($config)),
            'config' => new ArrayData($config),
            'multiple' => $config['maxNumberOfFiles'] !== 1,
            'displayInput' => (!isset($configOverwrite['maxNumberOfFiles']) || $configOverwrite['maxNumberOfFiles'])
        ))->renderWith($this->getTemplates());
    }

    public function getItemHandler($itemID)
    {
        $parentPage = $this->getRecord();
        return GalleryUploadField_ItemHandler::create($this, $itemID, $parentPage->ID);
    }

    public function sort($request)
    {

        $fileIDs = $request->postVar('ids');

        $record = $this->getRecord();
        $relName = $this->getName();
        $parentID = $record->ID;
        list($parentClass, $componentClass, $parentField, $componentField, $table) = $record->many_many($relName);

        if ($fileIDs && is_array($fileIDs)) foreach ($fileIDs as $order => $fileID) {
            $newOrder = $order + 1;

            $joinObj = $table::get()
                ->where("\"$parentField\" = '{$parentID}' AND \"$componentField\" = '{$fileID}'")
                ->first();

            if (!$joinObj || !$joinObj->exists()) {
                $joinObj = $table::create();
                $joinObj->$parentField = $parentID;
                $joinObj->$componentField = $fileID;
                $joinObj->write();
            }

            $joinObj->SortOrder = $newOrder;
            $joinObj->write();
        }
    }

    protected function attachFile($file)
    {

        $record = $this->getRecord();
        $name = $this->getName();
        list($parentClass, $componentClass, $parentField, $componentField, $table) = $record->many_many($name);

        if ($record && $record->exists()) {
            if ($record->has_many($name) || $record->many_many($name)) {
                if (!$record->isInDB()) $record->write();

                //Set the sort order first time image is attached
                $top = $table::get()
                    ->where("\"$parentField\" = '{$record->ID}'")
                    ->max('SortOrder');

                $top = (is_numeric($top)) ? $top + 1 : 1;

                $record->{$name}()->add($file, array('SortOrder' => $top));
            } elseif ($record->has_one($name)) {
                $record->{$name . 'ID'} = $file->ID;
                $record->write();
            }
        }
    }

    /**
     * Determines if the underlying record (if any) has a relationship
     * matching the field name. Important for permission control.
     *
     * @return boolean
     */
    public function managesRelation()
    {
        $record = $this->getRecord();
        $fieldName = $this->getName();
        return ($record
            && ($record->has_one($fieldName) || $record->has_many($fieldName) || $record->many_many($fieldName)));
    }

    /**
     * Need to call Gallery_PageExtension::OrderedImages() to get correct order
     * of images, cannot declare Imates() method in extension it won't be used
     */
    public function setValue($value, $record = null)
    {

        // If we're not passed a value directly, we can attempt to infer the field
        // value from the second parameter by inspecting its relations
        $items = new ArrayList();

        // Determine format of presented data
        if (empty($value) && $record) {

            // If a record is given as a second parameter, but no submitted values,
            // then we should inspect this instead for the form values
            if (($record instanceof DataObject) && $record->hasMethod('OrderedImages')) {
                // If given a dataobject use reflection to extract details

                $data = $record->OrderedImages();
                if ($data instanceof DataObject) {
                    // If has_one, add sole item to default list
                    $items->push($data);
                } elseif ($data instanceof SS_List) {
                    // For many_many and has_many relations we can use the relation list directly
                    $items = $data;
                }
            } elseif ($record instanceof SS_List) {
                // If directly passing a list then save the items directly
                $items = $record;
            }
        } elseif (!empty($value['Files'])) {
            // If value is given as an array (such as a posted form), extract File IDs from this
            $class = $this->getRelationAutosetClass();
            $items = DataObject::get($class)->byIDs($value['Files']);
        }

        // If javascript is disabled, direct file upload (non-html5 style) can
        // trigger a single or multiple file submission. Note that this may be
        // included in addition to re-submitted File IDs as above, so these
        // should be added to the list instead of operated on independently.
        if ($uploadedFiles = $this->extractUploadedFileData($value)) {
            foreach ($uploadedFiles as $tempFile) {
                $file = $this->saveTemporaryFile($tempFile, $error);
                if ($file) {
                    $items->add($file);
                } else {
                    throw new ValidationException($error);
                }
            }
        }

        // Filter items by what's allowed to be viewed
        $filteredItems = new ArrayList();
        $fileIDs = array();
        foreach ($items as $file) {
            if ($file->exists() && $file->canView()) {
                $filteredItems->push($file);
                $fileIDs[] = $file->ID;
            }
        }

        // Filter and cache updated item list
        $this->items = $filteredItems;
        // Same format as posted form values for this field. Also ensures that
        // $this->setValue($this->getValue()); is non-destructive
        $value = $fileIDs ? array('Files' => $fileIDs) : null;

        // To match FormField::setValue()
        $this->value = $value;
        return $this;
    }
}
